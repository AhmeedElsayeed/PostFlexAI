<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Client;
use App\Models\AudiencePersona;
use App\Models\AudienceCluster;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
{
    protected $offerService;

    public function __construct(OfferService $offerService)
    {
        $this->offerService = $offerService;
    }

    /**
     * Display a listing of offers
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Offer::where('team_id', Auth::user()->current_team_id);

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('persona_id')) {
            $query->byPersona($request->persona_id);
        }

        if ($request->has('segment_id')) {
            $query->bySegment($request->segment_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_auto_generated')) {
            $query->where('is_auto_generated', $request->boolean('is_auto_generated'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate
        $perPage = $request->input('per_page', 15);
        $offers = $query->paginate($perPage);

        return response()->json($offers);
    }

    /**
     * Store a newly created offer
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:discount,freebie,bundle,other',
            'value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_personas' => 'nullable|array',
            'target_segments' => 'nullable|array',
            'terms_conditions' => 'nullable|array',
            'max_usage_per_client' => 'nullable|integer|min:1',
            'total_usage_limit' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offer = $this->offerService->createOffer(
            $request->all(),
            Auth::user()->current_team_id
        );

        return response()->json($offer, 201);
    }

    /**
     * Display the specified offer
     *
     * @param Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Offer $offer)
    {
        $this->authorize('view', $offer);

        $offer->load(['coupons', 'personas', 'segments']);

        return response()->json($offer);
    }

    /**
     * Update the specified offer
     *
     * @param Request $request
     * @param Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Offer $offer)
    {
        $this->authorize('update', $offer);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:discount,freebie,bundle,other',
            'value' => 'sometimes|required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_personas' => 'nullable|array',
            'target_segments' => 'nullable|array',
            'terms_conditions' => 'nullable|array',
            'max_usage_per_client' => 'nullable|integer|min:1',
            'total_usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offer = $this->offerService->updateOffer($offer, $request->all());

        return response()->json($offer);
    }

    /**
     * Remove the specified offer
     *
     * @param Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Offer $offer)
    {
        $this->authorize('delete', $offer);

        $offer->delete();

        return response()->json(null, 204);
    }

    /**
     * Assign offer to personas
     *
     * @param Request $request
     * @param Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignToPersonas(Request $request, Offer $offer)
    {
        $this->authorize('update', $offer);

        $validator = Validator::make($request->all(), [
            'persona_ids' => 'required|array',
            'persona_ids.*' => 'exists:audience_personas,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offer = $this->offerService->assignToPersonas($offer, $request->persona_ids);

        return response()->json($offer);
    }

    /**
     * Assign offer to segments
     *
     * @param Request $request
     * @param Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignToSegments(Request $request, Offer $offer)
    {
        $this->authorize('update', $offer);

        $validator = Validator::make($request->all(), [
            'segment_ids' => 'required|array',
            'segment_ids.*' => 'exists:audience_clusters,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offer = $this->offerService->assignToSegments($offer, $request->segment_ids);

        return response()->json($offer);
    }

    /**
     * Generate coupons for the offer
     *
     * @param Request $request
     * @param Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateCoupons(Request $request, Offer $offer)
    {
        $this->authorize('update', $offer);

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'max_usage' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupons = $this->offerService->generateCoupons(
            $offer,
            $request->quantity,
            $request->max_usage
        );

        return response()->json($coupons, 201);
    }

    /**
     * Assign coupons to clients
     *
     * @param Request $request
     * @param Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignToClients(Request $request, Offer $offer)
    {
        $this->authorize('update', $offer);

        $validator = Validator::make($request->all(), [
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:clients,id',
            'max_usage' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupons = $this->offerService->assignCouponsToClients(
            $offer,
            $request->client_ids,
            $request->max_usage
        );

        return response()->json($coupons, 201);
    }

    /**
     * Get offer statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        $stats = $this->offerService->getOfferStats(Auth::user()->current_team_id);

        return response()->json($stats);
    }

    /**
     * Auto-generate offers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoGenerate()
    {
        $this->authorize('create', Offer::class);

        $offers = $this->offerService->autoGenerateOffers(Auth::user()->current_team_id);

        return response()->json($offers, 201);
    }
} 