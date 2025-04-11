<?php

namespace App\Http\Controllers;

use App\Models\InboxMessage;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'nullable|in:facebook,instagram,tiktok',
            'status' => 'nullable|in:new,read,replied,archived',
            'type' => 'nullable|in:comment,message'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $team = Team::findOrFail($request->user()->current_team_id);
        
        $query = InboxMessage::where('team_id', $team->id)
            ->with('user')
            ->latest('received_at');

        if ($request->platform) {
            $query->where('platform', $request->platform);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $messages = $query->paginate(20);

        return response()->json($messages);
    }

    public function reply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|string',
            'platform' => 'required|in:facebook,instagram,tiktok',
            'response' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $team = Team::findOrFail($request->user()->current_team_id);
        $message = InboxMessage::where('team_id', $team->id)
            ->where('platform', $request->platform)
            ->where('message_id', $request->message_id)
            ->firstOrFail();

        // TODO: Implement actual reply logic for each platform
        // This is a placeholder for the actual implementation
        $message->update([
            'status' => 'replied',
            'user_id' => $request->user()->id
        ]);

        return response()->json(['message' => 'Reply sent successfully']);
    }

    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_ids' => 'required|array',
            'message_ids.*' => 'required|integer',
            'status' => 'required|in:read,replied,archived'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $team = Team::findOrFail($request->user()->current_team_id);
        
        InboxMessage::where('team_id', $team->id)
            ->whereIn('id', $request->message_ids)
            ->update(['status' => $request->status]);

        return response()->json(['message' => 'Messages updated successfully']);
    }
} 