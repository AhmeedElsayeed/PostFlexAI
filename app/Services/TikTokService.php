<?php

namespace App\Services;

use App\Models\Team;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TikTokService
{
    protected $team;
    protected $client;
    protected $accessToken;

    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->client = new Client([
            'base_uri' => 'https://open.tiktokapis.com/v2/',
            'timeout' => 30.0,
        ]);
        $this->accessToken = $this->getAccessToken();
    }

    protected function getAccessToken()
    {
        // Get the latest valid access token for TikTok
        $account = $this->team->socialAccounts()
            ->where('platform', 'tiktok')
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$account) {
            throw new \Exception('No active TikTok account found for team');
        }

        return $account->access_token;
    }

    public function fetchMessages()
    {
        try {
            $response = $this->client->get('message/list', [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'fields' => 'id,text,from_user,create_time'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $messages = [];

            foreach ($data['data']['messages'] as $message) {
                $messages[] = [
                    'id' => $message['id'],
                    'sender_name' => $message['from_user']['username'],
                    'text' => $message['text'],
                    'type' => 'message',
                    'created_at' => $message['create_time']
                ];
            }

            return $messages;
        } catch (\Exception $e) {
            Log::error('TikTok message fetch error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendMessage($recipientId, $message)
    {
        try {
            $response = $this->client->post('message/send', [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'to_user_id' => $recipientId,
                    'text' => $message
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('TikTok message send error: ' . $e->getMessage());
            throw $e;
        }
    }
} 