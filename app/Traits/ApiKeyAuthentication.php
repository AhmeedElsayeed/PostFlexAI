<?php

namespace App\Traits;

use App\Services\ApiKeyService;
use Illuminate\Http\Request;

trait ApiKeyAuthentication
{
    protected $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Get the API key from the request
     *
     * @param Request $request
     * @return mixed
     */
    protected function getApiKey(Request $request)
    {
        return $request->api_key;
    }

    /**
     * Check if the API key has a specific permission
     *
     * @param Request $request
     * @param string $permission
     * @return bool
     */
    protected function hasApiKeyPermission(Request $request, string $permission)
    {
        $apiKey = $this->getApiKey($request);
        return $this->apiKeyService->hasPermission($apiKey, $permission);
    }

    /**
     * Get the user associated with the API key
     *
     * @param Request $request
     * @return mixed
     */
    protected function getApiKeyUser(Request $request)
    {
        $apiKey = $this->getApiKey($request);
        return $apiKey->user;
    }
} 