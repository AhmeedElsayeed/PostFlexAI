<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\OpenAI;

class ThreadController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
            'platform' => 'required|string|in:twitter,linkedin',
            'tone' => 'required|string|in:professional,casual,humorous',
            'length' => 'required|integer|min:3|max:10'
        ]);

        // Generate thread using OpenAI
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a social media expert. Create a thread of {$request->length} tweets about {$request->topic} in a {$request->tone} tone."
                ]
            ]
        ]);

        $threadContent = $response->choices[0]->message->content;
        $posts = $this->parseThreadContent($threadContent);

        $thread = Thread::create([
            'title' => $request->topic,
            'description' => "Generated thread about {$request->topic}",
            'user_id' => Auth::id(),
            'platform' => $request->platform,
            'status' => 'draft'
        ]);

        foreach ($posts as $index => $content) {
            Post::create([
                'thread_id' => $thread->id,
                'content' => $content,
                'order' => $index + 1,
                'status' => 'draft'
            ]);
        }

        return response()->json([
            'message' => 'Thread generated successfully',
            'data' => $thread->load('posts')
        ]);
    }

    public function schedule(Request $request, Thread $thread)
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now'
        ]);

        $thread->update([
            'status' => 'scheduled',
            'scheduled_at' => $request->scheduled_at
        ]);

        // Schedule posts
        foreach ($thread->posts as $post) {
            $post->update([
                'scheduled_at' => $request->scheduled_at
            ]);
        }

        return response()->json([
            'message' => 'Thread scheduled successfully',
            'data' => $thread
        ]);
    }

    public function publish(Thread $thread)
    {
        $thread->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        // Publish posts
        foreach ($thread->posts as $post) {
            $post->update([
                'status' => 'published',
                'published_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'Thread published successfully',
            'data' => $thread
        ]);
    }

    private function parseThreadContent($content)
    {
        // Split content into individual posts
        // This is a simple implementation, might need to be more sophisticated
        return array_filter(explode("\n\n", $content));
    }
} 