<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PostApprovalService;

/**
 * @OA\Tag(
 *     name="Post Approvals",
 *     description="API Endpoints for post approval workflow"
 * )
 */
class PostApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(PostApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * @OA\Get(
     *     path="/api/posts/pending",
     *     summary="Get pending posts for approval",
     *     tags={"Post Approvals"},
     *     @OA\Response(response="200", description="List of pending posts")
     * )
     */
    public function getPendingPosts()
    {
        $posts = $this->approvalService->getPendingPosts();
        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/api/posts/{id}/approve",
     *     summary="Approve a post",
     *     tags={"Post Approvals"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Post ID"),
     *     @OA\Response(response="200", description="Post approved successfully")
     * )
     */
    public function approvePost($id)
    {
        $result = $this->approvalService->approvePost($id);
        return response()->json($result);
    }

    /**
     * @OA\Post(
     *     path="/api/posts/{id}/reject",
     *     summary="Reject a post",
     *     tags={"Post Approvals"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Post ID"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", description="Rejection reason")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Post rejected successfully")
     * )
     */
    public function rejectPost(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $result = $this->approvalService->rejectPost($id, $request->reason);
        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/api/posts/approval-history",
     *     summary="Get post approval history",
     *     tags={"Post Approvals"},
     *     @OA\Response(response="200", description="Approval history")
     * )
     */
    public function getApprovalHistory()
    {
        $history = $this->approvalService->getApprovalHistory();
        return response()->json($history);
    }
} 