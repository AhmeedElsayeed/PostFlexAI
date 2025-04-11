<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function requestApproval(Request $request, Post $post)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $approvalRequest = ApprovalRequest::create([
            'post_id' => $post->id,
            'requested_by' => Auth::id(),
            'status' => 'pending',
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Approval request created successfully',
            'data' => $approvalRequest
        ]);
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $approvalRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes' => $request->notes ?? $approvalRequest->notes
        ]);

        return response()->json([
            'message' => 'Post approved successfully',
            'data' => $approvalRequest
        ]);
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $request->validate([
            'notes' => 'required|string'
        ]);

        $approvalRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'rejected_at' => now(),
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Post rejected successfully',
            'data' => $approvalRequest
        ]);
    }

    public function pendingApprovals()
    {
        $pendingApprovals = ApprovalRequest::with(['post', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $pendingApprovals
        ]);
    }
} 