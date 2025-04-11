<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserInTeam
{
    public function handle(Request $request, Closure $next)
    {
        $teamId = $request->route('team') ?? $request->input('team_id');
        
        if (!$teamId) {
            return response()->json(['message' => 'Team ID is required'], 400);
        }

        $user = $request->user();
        $isMember = $user->teams()->where('teams.id', $teamId)->exists();
        $isOwner = $user->ownedTeams()->where('id', $teamId)->exists();

        if (!$isMember && !$isOwner) {
            return response()->json(['message' => 'You are not a member of this team'], 403);
        }

        return $next($request);
    }
} 