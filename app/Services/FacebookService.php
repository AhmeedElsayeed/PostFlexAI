<?php

namespace App\Services;

use App\Models\Team;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    protected $team;
    protected $client;
    protected $accessToken;

    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->client = new Client([
            'base_uri' => 'https://graph.facebook.com/v18.0/',
            'timeout' => 30.0,
        ]);
        $this->accessToken = $this->getAccessToken();
    }

    protected function getAccessToken()
    {
        // Get the latest valid access token for Facebook
        $account = $this->team->socialAccounts()
            ->where('platform', 'facebook')
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$account) {
            throw new \Exception('No active Facebook account found for team');
        }

        return $account->access_token;
    }

    public function fetchMessages()
    {
        try {
            $response = $this->client->get('me/conversations', [
                'query' => [
                    'access_token' => $this->accessToken,
                    'fields' => 'messages{message,from,created_time}'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $messages = [];

            foreach ($data['data'] as $conversation) {
                if (isset($conversation['messages']['data'])) {
                    foreach ($conversation['messages']['data'] as $message) {
                        $messages[] = [
                            'id' => $message['id'],
                            'sender_name' => $message['from']['name'],
                            'text' => $message['message'],
                            'type' => 'message',
                            'created_at' => $message['created_time']
                        ];
                    }
                }
            }

            return $messages;
        } catch (\Exception $e) {
            Log::error('Facebook message fetch error: ' . $e->getMessage());
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
            Log::error('Facebook message send error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPostInsights($postId)
    {
        try {
            $response = $this->client->get("{$postId}/insights", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'metric' => 'post_impressions,post_reactions_by_type,post_clicks,post_shares'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            // Process metrics
            $insights = [
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'views' => 0,
                'saves' => 0,
                'engagement_rate' => 0
            ];

            foreach ($data['data'] as $metric) {
                switch ($metric['name']) {
                    case 'post_reactions_by_type':
                        $insights['likes'] = $metric['values'][0]['value']['like'] ?? 0;
                        break;
                    case 'post_impressions':
                        $insights['views'] = $metric['values'][0]['value'] ?? 0;
                        break;
                    case 'post_shares':
                        $insights['shares'] = $metric['values'][0]['value'] ?? 0;
                        break;
                }
            }

            // Get comments count
            $commentsResponse = $this->client->get("{$postId}/comments", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'summary' => true
                ]
            ]);
            $commentsData = json_decode($commentsResponse->getBody(), true);
            $insights['comments'] = $commentsData['summary']['total_count'] ?? 0;

            // Calculate engagement rate
            if ($insights['views'] > 0) {
                $totalEngagement = $insights['likes'] + $insights['comments'] + $insights['shares'];
                $insights['engagement_rate'] = round(($totalEngagement / $insights['views']) * 100, 2);
            }

            return $insights;
        } catch (\Exception $e) {
            Log::error("Facebook insights error for post {$postId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAccountStats($accountId)
    {
        try {
            $response = $this->client->get("{$accountId}", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'fields' => 'followers_count,media_count,insights.metric(reach,impressions)'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            $stats = [
                'followers' => $data['followers_count'] ?? 0,
                'posts_count' => $data['media_count'] ?? 0,
                'reach' => 0,
                'impressions' => 0,
                'engagement_rate' => 0
            ];

            // Process insights
            if (isset($data['insights']['data'])) {
                foreach ($data['insights']['data'] as $metric) {
                    switch ($metric['name']) {
                        case 'reach':
                            $stats['reach'] = $metric['values'][0]['value'] ?? 0;
                            break;
                        case 'impressions':
                            $stats['impressions'] = $metric['values'][0]['value'] ?? 0;
                            break;
                    }
                }
            }

            // Calculate engagement rate
            if ($stats['reach'] > 0) {
                $stats['engagement_rate'] = round(($stats['impressions'] / $stats['reach']) * 100, 2);
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error("Facebook account stats error for account {$accountId}: " . $e->getMessage());
            throw $e;
        }
    }
} 