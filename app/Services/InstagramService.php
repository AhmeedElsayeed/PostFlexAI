<?php

namespace App\Services;

use App\Models\Team;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    protected $team;
    protected $client;
    protected $accessToken;
    protected $instagramBusinessId;

    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->client = new Client([
            'base_uri' => 'https://graph.facebook.com/v18.0/',
            'timeout' => 30.0,
        ]);
        $this->accessToken = $this->getAccessToken();
        $this->instagramBusinessId = $this->getInstagramBusinessId();
    }

    protected function getAccessToken()
    {
        // Get the latest valid access token for Instagram
        $account = $this->team->socialAccounts()
            ->where('platform', 'instagram')
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$account) {
            throw new \Exception('No active Instagram account found for team');
        }

        return $account->access_token;
    }

    public function fetchMessages()
    {
        try {
            // First get the Instagram Business Account ID
            $response = $this->client->get('me/accounts', [
                'query' => [
                    'access_token' => $this->accessToken,
                    'fields' => 'instagram_business_account'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $instagramAccountId = $data['data'][0]['instagram_business_account']['id'];

            // Now fetch messages
            $response = $this->client->get("{$instagramAccountId}/conversations", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'fields' => 'messages{text,from,created_time}'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $messages = [];

            foreach ($data['data'] as $conversation) {
                if (isset($conversation['messages']['data'])) {
                    foreach ($conversation['messages']['data'] as $message) {
                        $messages[] = [
                            'id' => $message['id'],
                            'sender_name' => $message['from']['username'],
                            'text' => $message['text'],
                            'type' => 'message',
                            'created_at' => $message['created_time']
                        ];
                    }
                }
            }

            return $messages;
        } catch (\Exception $e) {
            Log::error('Instagram message fetch error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendMessage($recipientId, $message)
    {
        try {
            $response = $this->client->post('me/messages', [
                'query' => ['access_token' => $this->accessToken],
                'json' => [
                    'recipient' => ['id' => $recipientId],
                    'message' => ['text' => $message]
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Instagram message send error: ' . $e->getMessage());
            throw $e;
        }
    }
} 