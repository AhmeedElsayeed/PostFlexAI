<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\InboxMessage;
use App\Services\ClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(Request $request)
    {
        $query = Client::query()
            ->where('team_id', Auth::user()->team_id)
            ->when($request->search, function ($q, $search) {
                return $q->search($search);
            })
            ->when($request->status, function ($q, $status) {
                return $q->byStatus($status);
            })
            ->when($request->platform, function ($q, $platform) {
                return $q->byPlatform($platform);
            })
            ->when($request->tag, function ($q, $tag) {
                return $q->byTag($tag);
            })
            ->when($request->active, function ($q) {
                return $q->active();
            });

        $clients = $query->with(['notes' => function ($q) {
                $q->recent()->take(5);
            }])
            ->orderBy('last_interaction_at', 'desc')
            ->paginate(15);

        return response()->json([
            'clients' => $clients,
            'stats' => $this->clientService->getTeamStats(Auth::user()->team_id)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'platform' => 'required|string|max:50',
            'profile_link' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:new,interested,vip,unresponsive',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        $client = Client::create([
            'team_id' => Auth::user()->team_id,
            ...$validated
        ]);

        return response()->json($client, 201);
    }

    public function show(Client $client)
    {
        Gate::authorize('view', $client);

        $client->load([
            'notes' => function ($q) {
                $q->with('user:id,name')
                  ->recent()
                  ->take(10);
            },
            'messages' => function ($q) {
                $q->recent()
                  ->take(20);
            },
            'posts' => function ($q) {
                $q->recent()
                  ->take(10);
            }
        ]);

        return response()->json([
            'client' => $client,
            'stats' => $client->getInteractionStats(),
            'recommendations' => $this->clientService->getRecommendations($client)
        ]);
    }

    public function update(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'username' => 'nullable|string|max:255',
            'platform' => 'string|max:50',
            'profile_link' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'in:new,interested,vip,unresponsive',
            'tags' => 'array',
            'tags.*' => 'string|max:50'
        ]);

        $client->update($validated);

        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        Gate::authorize('delete', $client);

        $client->delete();

        return response()->noContent();
    }

    public function addTag(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'tag' => 'required|string|max:50'
        ]);

        $client->addTag($validated['tag']);

        return response()->json($client);
    }

    public function removeTag(Request $request, Client $client)
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'tag' => 'required|string|max:50'
        ]);

        $client->removeTag($validated['tag']);

        return response()->json($client);
    }

    public function linkMessage(Request $request, Client $client, InboxMessage $message)
    {
        Gate::authorize('update', $client);

        $message->update(['client_id' => $client->id]);
        $client->updateLastInteraction();

        return response()->json([
            'message' => 'Message linked successfully',
            'client' => $client->fresh()
        ]);
    }

    public function export(Request $request)
    {
        Gate::authorize('export-clients');

        $clients = Client::where('team_id', Auth::user()->team_id)
            ->with(['notes', 'messages'])
            ->get();

        return $this->clientService->exportToCsv($clients);
    }
} 