<?php

namespace App\Http\Middleware;

use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;

class ApiKeyAuthentication
{
    protected $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json(['message' => 'مفتاح API مطلوب'], 401);
        }

        $validApiKey = $this->apiKeyService->validate($apiKey);

        if (!$validApiKey) {
            return response()->json(['message' => 'مفتاح API غير صالح'], 401);
        }

        // Add the API key and its associated user to the request
        $request->merge(['api_key' => $validApiKey]);
        $request->setUserResolver(function () use ($validApiKey) {
            return $validApiKey->user;
        });

        return $next($request);
    }
} 