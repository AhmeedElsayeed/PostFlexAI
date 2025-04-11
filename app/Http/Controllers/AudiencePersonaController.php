<?php

namespace App\Http\Controllers;

use App\Models\AudiencePersona;
use App\Models\SocialAccount;
use App\Models\Team;
use App\Services\AudiencePersonaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AudiencePersonaController extends Controller
{
    protected $personaService;

    public function __construct(AudiencePersonaService $personaService)
    {
        $this->personaService = $personaService;
    }

    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        $account = $request->query('account_id') 
            ? SocialAccount::findOrFail($request->query('account_id'))
            : null;

        // Check if account belongs to team
        if ($account && !$team->socialAccounts->contains($account)) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $query = AudiencePersona::query()
            ->where('team_id', $team->id)
            ->when($account, function ($query) use ($account) {
                return $query->where('social_account_id', $account->id);
            })
            ->when($request->query('auto_generated'), function ($query, $value) {
                return $query->where('is_auto_generated', $value === 'true');
            })
            ->when($request->query('search'), function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            });

        $personas = $query->paginate(10);

        return response()->json($personas);
    }

    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;
        $account = $request->input('social_account_id') 
            ? SocialAccount::findOrFail($request->input('social_account_id'))
            : null;

        // Check if account belongs to team
        if ($account && !$team->socialAccounts->contains($account)) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'age_range_start' => 'required|integer|min:13|max:100',
            'age_range_end' => 'required|integer|min:13|max:100|gte:age_range_start',
            'gender' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:100',
            'pain_points' => 'nullable|array',
            'pain_points.*' => 'string|max:255',
            'goals' => 'nullable|array',
            'goals.*' => 'string|max:255',
            'behaviors' => 'nullable|array',
            'behaviors.*' => 'string|max:255',
            'preferred_content_types' => 'nullable|array',
            'preferred_content_types.*' => 'string|max:100',
            'active_hours' => 'nullable|array',
            'active_hours.*' => 'integer|min:0|max:23',
            'description' => 'nullable|string|max:1000',
        ]);

        $persona = AudiencePersona::create([
            'team_id' => $team->id,
            'social_account_id' => $account?->id,
            'is_auto_generated' => false,
            ...$validated
        ]);

        return response()->json($persona, 201);
    }

    public function show(AudiencePersona $persona)
    {
        $this->authorize('view', $persona);

        return response()->json($persona->load('clients'));
    }

    public function update(Request $request, AudiencePersona $persona)
    {
        $this->authorize('update', $persona);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'age_range_start' => 'sometimes|required|integer|min:13|max:100',
            'age_range_end' => 'sometimes|required|integer|min:13|max:100|gte:age_range_start',
            'gender' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:100',
            'pain_points' => 'nullable|array',
            'pain_points.*' => 'string|max:255',
            'goals' => 'nullable|array',
            'goals.*' => 'string|max:255',
            'behaviors' => 'nullable|array',
            'behaviors.*' => 'string|max:255',
            'preferred_content_types' => 'nullable|array',
            'preferred_content_types.*' => 'string|max:100',
            'active_hours' => 'nullable|array',
            'active_hours.*' => 'integer|min:0|max:23',
            'description' => 'nullable|string|max:1000',
        ]);

        $persona->update($validated);

        return response()->json($persona);
    }

    public function destroy(AudiencePersona $persona)
    {
        $this->authorize('delete', $persona);

        $persona->delete();

        return response()->json(null, 204);
    }

    public function generate(Request $request)
    {
        $team = $request->user()->currentTeam;
        $account = $request->input('social_account_id') 
            ? SocialAccount::findOrFail($request->input('social_account_id'))
            : null;

        // Check if account belongs to team
        if ($account && !$team->socialAccounts->contains($account)) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $personas = $this->personaService->generatePersonas($team, $account);

        return response()->json($personas);
    }

    public function recommendations(AudiencePersona $persona)
    {
        $this->authorize('view', $persona);

        $recommendations = [
            'content' => $persona->getTopRecommendations(),
            'timing' => [
                'best_days' => $persona->active_hours,
                'best_hours' => $persona->active_hours,
            ],
            'engagement' => [
                'rate' => $persona->engagement_rate,
                'label' => $persona->getEngagementRateLabel(),
            ],
            'interests' => $persona->getTopInterests(),
            'pain_points' => $persona->getTopPainPoints(),
            'goals' => $persona->getTopGoals(),
        ];

        return response()->json($recommendations);
    }
} 