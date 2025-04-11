<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query()
            ->with(['offer', 'client'])
            ->when($request->status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($request->offer_id, function ($q, $offerId) {
                return $q->where('offer_id', $offerId);
            })
            ->when($request->client_id, function ($q, $clientId) {
                return $q->where('client_id', $clientId);
            })
            ->when($request->search, function ($q, $search) {
                return $q->where('code', 'like', "%{$search}%");
            });

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'client_id' => 'nullable|exists:clients,id',
            'max_usage' => 'nullable|integer|min:1',
            'code' => 'nullable|string|unique:coupons,code'
        ]);

        // Generate unique code if not provided
        if (!isset($validated['code'])) {
            do {
                $validated['code'] = strtoupper(Str::random(8));
            } while (Coupon::where('code', $validated['code'])->exists());
        }

        $coupon = Coupon::create($validated);

        return response()->json($coupon->load(['offer', 'client']), 201);
    }

    public function show(Coupon $coupon)
    {
        return response()->json($coupon->load(['offer', 'client']));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'max_usage' => 'nullable|integer|min:1',
            'code' => [
                'nullable',
                'string',
                Rule::unique('coupons')->ignore($coupon->id)
            ]
        ]);

        $coupon->update($validated);

        return response()->json($coupon->load(['offer', 'client']));
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(null, 204);
    }

    public function redeem(Coupon $coupon)
    {
        if (!$coupon->canBeUsed()) {
            return response()->json([
                'message' => 'Coupon cannot be used at this time'
            ], 422);
        }

        $coupon->markAsUsed();

        return response()->json([
            'message' => 'Coupon redeemed successfully',
            'coupon' => $coupon->load(['offer', 'client'])
        ]);
    }

    public function generateBulk(Request $request)
    {
        $validated = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'quantity' => 'required|integer|min:1|max:100',
            'max_usage' => 'nullable|integer|min:1'
        ]);

        $coupons = collect();
        $offer = Offer::findOrFail($validated['offer_id']);

        for ($i = 0; $i < $validated['quantity']; $i++) {
            do {
                $code = strtoupper(Str::random(8));
            } while (Coupon::where('code', $code)->exists());

            $coupons->push(Coupon::create([
                'offer_id' => $offer->id,
                'code' => $code,
                'max_usage' => $validated['max_usage'] ?? null
            ]));
        }

        return response()->json([
            'message' => 'Coupons generated successfully',
            'coupons' => $coupons->load('offer')
        ], 201);
    }

    public function assignToClient(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id'
        ]);

        if ($coupon->client_id) {
            return response()->json([
                'message' => 'Coupon is already assigned to a client'
            ], 422);
        }

        $coupon->update(['client_id' => $validated['client_id']]);

        return response()->json([
            'message' => 'Coupon assigned successfully',
            'coupon' => $coupon->load(['offer', 'client'])
        ]);
    }

    public function stats(Request $request)
    {
        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('status', 'active')->count(),
            'used' => Coupon::where('status', 'used')->count(),
            'expired' => Coupon::where('status', 'expired')->count(),
            'by_offer' => Coupon::selectRaw('offer_id, count(*) as count')
                ->groupBy('offer_id')
                ->with('offer:id,title')
                ->get(),
            'recent_activity' => Coupon::with(['offer', 'client'])
                ->whereNotNull('redeemed_at')
                ->orderBy('redeemed_at', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json($stats);
    }
} 