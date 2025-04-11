<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TeamController extends Controller
{
    public function createTeam(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::create([
            'name' => $request->name,
            'owner_id' => $request->user()->id,
        ]);

        // Add owner as team member with admin role
        $team->members()->attach($request->user()->id, ['role' => 'admin']);

        $this->logActivity($team, $request->user(), 'create_team', 'Created new team');

        return response()->json($team->load('owner', 'members'), 201);
    }

    public function listMembers(Request $request)
    {
        $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
        ]);

        $team = Team::findOrFail($request->team_id);
        return response()->json($team->members()->with('roles')->get());
    }

    public function addMember(Request $request)
    {
        $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,editor,viewer'],
        ]);

        $team = Team::findOrFail($request->team_id);
        
        // Check if user is team owner
        if ($team->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Only team owner can add members'], 403);
        }

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        
        if ($user) {
            // Add existing user to team
            if (!$team->members()->where('users.id', $user->id)->exists()) {
                $team->members()->attach($user->id, ['role' => $request->role]);
                $this->logActivity($team, $request->user(), 'add_member', "Added member {$user->email}");
            }
        } else {
            // TODO: Send invitation email to new user
            // For now, we'll just return a message
            return response()->json(['message' => 'User not found. Invitation system will be implemented.'], 404);
        }

        return response()->json($team->load('members'));
    }

    public function removeMember(Request $request)
    {
        $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $team = Team::findOrFail($request->team_id);
        
        // Check if user is team owner
        if ($team->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Only team owner can remove members'], 403);
        }

        // Prevent removing the owner
        if ($request->user_id == $team->owner_id) {
            return response()->json(['message' => 'Cannot remove team owner'], 400);
        }

        $team->members()->detach($request->user_id);
        $this->logActivity($team, $request->user(), 'remove_member', "Removed member ID {$request->user_id}");

        return response()->json(['message' => 'Member removed successfully']);
    }

    public function changeMemberRole(Request $request)
    {
        $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:admin,editor,viewer'],
        ]);

        $team = Team::findOrFail($request->team_id);
        
        // Check if user is team owner
        if ($team->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Only team owner can change member roles'], 403);
        }

        // Prevent changing owner's role
        if ($request->user_id == $team->owner_id) {
            return response()->json(['message' => 'Cannot change team owner role'], 400);
        }

        $team->members()->updateExistingPivot($request->user_id, ['role' => $request->role]);
        $this->logActivity($team, $request->user(), 'change_role', "Changed member ID {$request->user_id} role to {$request->role}");

        return response()->json(['message' => 'Member role updated successfully']);
    }

    private function logActivity(Team $team, User $user, string $action, string $description, array $metadata = [])
    {
        UserActivityLog::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
} 