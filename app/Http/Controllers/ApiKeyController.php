<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Services\ApiKeyService;
use App\Http\Requests\CreateApiKeyRequest;
use App\Http\Requests\ExtendApiKeyRequest;
use App\Traits\ApiKeyAuthentication;
use App\Http\Resources\ApiKeyResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiKeyController extends Controller
{
    use ApiKeyAuthentication;

    protected $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Create a new API key
     *
     * @param CreateApiKeyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateApiKeyRequest $request)
    {
        $apiKey = $this->apiKeyService->create(
            $request->user(),
            $request->name,
            $request->permissions ?? [],
            $request->expires_at ? new \DateTime($request->expires_at) : null
        );

        return response()->json([
            'message' => 'تم إنشاء مفتاح API بنجاح',
            'api_key' => $apiKey->plain_key,
            'data' => new ApiKeyResource($apiKey)
        ], 201);
    }

    /**
     * List all API keys for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $apiKeys = $this->apiKeyService->getAllForUser($request->user());

        return response()->json([
            'data' => ApiKeyResource::collection($apiKeys)
        ]);
    }

    /**
     * Revoke an API key
     *
     * @param Request $request
     * @param ApiKey $apiKey
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke(Request $request, ApiKey $apiKey)
    {
        if ($apiKey->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بإلغاء هذا المفتاح'], 403);
        }

        $this->apiKeyService->revoke($apiKey);

        return response()->json([
            'message' => 'تم إلغاء مفتاح API بنجاح',
            'data' => new ApiKeyResource($apiKey)
        ]);
    }

    /**
     * Extend the expiration of an API key
     *
     * @param ExtendApiKeyRequest $request
     * @param ApiKey $apiKey
     * @return \Illuminate\Http\JsonResponse
     */
    public function extend(ExtendApiKeyRequest $request, ApiKey $apiKey)
    {
        if ($apiKey->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بتمديد هذا المفتاح'], 403);
        }

        $this->apiKeyService->extendExpiration($apiKey, $request->days);

        return response()->json([
            'message' => 'تم تمديد صلاحية مفتاح API بنجاح',
            'data' => new ApiKeyResource($apiKey)
        ]);
    }

    /**
     * Set an API key to never expire
     *
     * @param Request $request
     * @param ApiKey $apiKey
     * @return \Illuminate\Http\JsonResponse
     */
    public function setNeverExpire(Request $request, ApiKey $apiKey)
    {
        if ($apiKey->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا المفتاح'], 403);
        }

        $this->apiKeyService->setNeverExpire($apiKey);

        return response()->json([
            'message' => 'تم تعيين مفتاح API لعدم انتهاء الصلاحية بنجاح',
            'data' => new ApiKeyResource($apiKey)
        ]);
    }
} 