<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $team = Team::findOrFail($request->user()->current_team_id);
        $posts = Post::where('team_id', $team->id)
            ->with(['user', 'media'])
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'type' => 'required|in:image,video,story,reel,carousel',
            'scheduled_at' => 'nullable|date',
            'platforms' => 'required|array',
            'platforms.*' => 'in:facebook,instagram,tiktok',
            'media' => 'required|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,mp4,mov|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $team = Team::findOrFail($request->user()->current_team_id);

        $post = Post::create([
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'caption' => $request->caption,
            'type' => $request->type,
            'status' => $request->scheduled_at ? 'scheduled' : 'draft',
            'scheduled_at' => $request->scheduled_at,
            'platforms' => $request->platforms
        ]);

        foreach ($request->file('media') as $file) {
            $path = $file->store('posts/' . $post->id, 'public');
            
            PostMedia::create([
                'post_id' => $post->id,
                'file_path' => $path,
                'media_type' => $file->getClientOriginalExtension() === 'mp4' ? 'video' : 'image'
            ]);
        }

        return response()->json($post->load('media'), 201);
    }

    public function show(Request $request, $id)
    {
        $team = Team::findOrFail($request->user()->current_team_id);
        $post = Post::where('team_id', $team->id)
            ->with(['user', 'media'])
            ->findOrFail($id);

        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'platforms' => 'array',
            'platforms.*' => 'in:facebook,instagram,tiktok'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $team = Team::findOrFail($request->user()->current_team_id);
        $post = Post::where('team_id', $team->id)->findOrFail($id);

        if ($post->isPosted()) {
            return response()->json(['message' => 'Cannot update a posted post'], 403);
        }

        $post->update($request->only(['title', 'caption', 'scheduled_at', 'platforms']));

        return response()->json($post->load('media'));
    }

    public function destroy(Request $request, $id)
    {
        $team = Team::findOrFail($request->user()->current_team_id);
        $post = Post::where('team_id', $team->id)->findOrFail($id);

        if ($post->isPosted()) {
            return response()->json(['message' => 'Cannot delete a posted post'], 403);
        }

        // Delete media files
        foreach ($post->media as $media) {
            Storage::disk('public')->delete($media->file_path);
        }

        $post->delete();

        return response()->json(null, 204);
    }

    public function publishNow(Request $request, $id)
    {
        $team = Team::findOrFail($request->user()->current_team_id);
        $post = Post::where('team_id', $team->id)->findOrFail($id);

        if (!$post->isDraft()) {
            return response()->json(['message' => 'Only draft posts can be published immediately'], 403);
        }

        // TODO: Implement actual publishing logic
        $post->update([
            'status' => 'posted',
            'scheduled_at' => now()
        ]);

        return response()->json($post->load('media'));
    }
} 