<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\SmartReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmartReplyController extends Controller
{
    protected SmartReplyService $smartReplyService;

    public function __construct(SmartReplyService $smartReplyService)
    {
        $this->smartReplyService = $smartReplyService;
    }

    /**
     * Generate a smart reply for a message
     */
    public function generateReply(Message $message): JsonResponse
    {
        try {
            $reply = $this->smartReplyService->generateReply($message);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $message->id,
                    'reply' => $reply
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate smart reply',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reply templates
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $templates = $this->smartReplyService->getReplyTemplates();
            
            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reply templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update reply templates
     */
    public function updateTemplates(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'templates' => 'required|array',
                'templates.*' => 'required|array',
                'templates.*.*' => 'required|string'
            ]);

            $this->smartReplyService->updateReplyTemplates($request->templates);
            
            return response()->json([
                'success' => true,
                'message' => 'Reply templates updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reply templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 