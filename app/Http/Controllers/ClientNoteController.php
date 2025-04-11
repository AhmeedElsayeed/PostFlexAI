<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ClientNoteController extends Controller
{
    public function index(Request $request, Client $client)
    {
        Gate::authorize('view', $client);

        $query = $client->notes()
            ->with('user:id,name')
            ->when($request->type, function ($q, $type) {
                return $q->byType($type);
            })
            ->when($request->user_id, function ($q, $userId) {
                return $q->byUser($userId);
            })
            ->when($request->private === 'true', function ($q) {
                return $q->private();
            })
            ->when($request->private === 'false', function ($q) {
                return $q->public();
            });

        $notes = $query->recent()->paginate(15);

        return response()->json($notes);
    }

    public function store(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'note' => 'required|string',
            'type' => 'required|string|in:general,follow_up,important',
            'is_private' => 'boolean'
        ]);

        $note = $client->notes()->create([
            'user_id' => Auth::id(),
            ...$validated
        ]);

        $note->load('user:id,name');

        return response()->json($note, 201);
    }

    public function update(Request $request, Client $client, ClientNote $note)
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'note' => 'string',
            'type' => 'string|in:general,follow_up,important',
            'is_private' => 'boolean'
        ]);

        $note->update($validated);

        return response()->json($note);
    }

    public function destroy(Client $client, ClientNote $note)
    {
        Gate::authorize('update', $client);

        $note->delete();

        return response()->noContent();
    }
} 