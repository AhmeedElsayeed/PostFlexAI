<?php

namespace App\Http\Controllers;

use App\Models\AutoReply;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AutoReplyController extends Controller
{
    public function index(Request $request)
    {
        $team = Team::findOrFail($request->user()->current_team_id);
        $autoReplies = AutoReply::where('team_id', $team->id)
            ->latest()
            ->paginate(10);

        return response()->json($autoReplies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trigger_keyword' => 'required|string|max:255',
            'response_text' => 'required|string',
            'platform' => 'required|in:facebook,instagram,tiktok'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $team = Team::findOrFail($request->user()->current_team_id);

        $autoReply = AutoReply::create([
            'team_id' => $team->id,
            'trigger_keyword' => $request->trigger_keyword,
            'response_text' => $request->response_text,
            'platform' => $request->platform
        ]);

        return response()->json($autoReply, 201);
    }

    public function destroy(Request $request, $id)
    {
        $team = Team::findOrFail($request->user()->current_team_id);
        $autoReply = AutoReply::where('team_id', $team->id)->findOrFail($id);
        
        $autoReply->delete();

        return response()->json(null, 204);
    }
} 