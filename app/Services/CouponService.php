<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponService
{
    /**
     * Generate a unique coupon code
     *
     * @return string
     */
    public function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Coupon::where('code', $code)->exists());
        
        return $code;
    }
    
    /**
     * Create a new coupon
     *
     * @param array $data
     * @return Coupon
     */
    public function createCoupon(array $data): Coupon
    {
        // Generate a unique code if not provided
        if (!isset($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        }
        
        return Coupon::create($data);
    }
    
    /**
     * Create multiple coupons for an offer
     *
     * @param Offer $offer
     * @param int $quantity
     * @param int|null $maxUsage
     * @return array
     */
    public function createBulkCoupons(Offer $offer, int $quantity, ?int $maxUsage = null): array
    {
        $coupons = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            $coupons[] = $this->createCoupon([
                'offer_id' => $offer->id,
                'max_usage' => $maxUsage
            ]);
        }
        
        return $coupons;
    }
    
    /**
     * Assign a coupon to a client
     *
     * @param Coupon $coupon
     * @param Client $client
     * @return Coupon
     */
    public function assignToClient(Coupon $coupon, Client $client): Coupon
    {
        $coupon->client_id = $client->id;
        $coupon->save();
        
        return $coupon;
    }
    
    /**
     * Redeem a coupon
     *
     * @param Coupon $coupon
     * @return bool
     */
    public function redeemCoupon(Coupon $coupon): bool
    {
        if (!$coupon->canBeUsed()) {
            return false;
        }
        
        return $coupon->markAsUsed();
    }
    
    /**
     * Mark a coupon as expired
     *
     * @param Coupon $coupon
     * @return bool
     */
    public function expireCoupon(Coupon $coupon): bool
    {
        if ($coupon->status === 'used') {
            return false;
        }
        
        return $coupon->markAsExpired();
    }
    
    /**
     * Get coupons for a client
     *
     * @param Client $client
     * @param string|null $status
     * @return array
     */
    public function getClientCoupons(Client $client, ?string $status = null): array
    {
        $query = Coupon::where('client_id', $client->id)
            ->with('offer');
            
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get()->toArray();
    }
    
    /**
     * Get active coupons for a client
     *
     * @param Client $client
     * @return array
     */
    public function getActiveClientCoupons(Client $client): array
    {
        return Coupon::where('client_id', $client->id)
            ->where('status', 'active')
            ->whereHas('offer', function ($q) {
                $q->where('is_active', true)
                    ->where(function ($sq) {
                        $sq->whereNull('start_date')
                            ->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($sq) {
                        $sq->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
            })
            ->where(function ($q) {
                $q->whereNull('max_usage')
                    ->orWhereRaw('times_used < max_usage');
            })
            ->with('offer')
            ->get()
            ->toArray();
    }
    
    /**
     * Auto-assign coupons to clients based on their personas
     *
     * @param Offer $offer
     * @return array
     */
    public function autoAssignCoupons(Offer $offer): array
    {
        // Get eligible clients based on personas and segments
        $eligibleClientIds = app(OfferService::class)->getEligibleClients($offer);
        
        if (empty($eligibleClientIds)) {
            return [];
        }
        
        // Assign coupons to eligible clients
        return app(OfferService::class)->assignCouponsToClients(
            $offer, 
            $eligibleClientIds, 
            $offer->max_usage_per_client
        );
    }
    
    /**
     * Get coupon usage statistics
     *
     * @param int $teamId
     * @return array
     */
    public function getCouponStats(int $teamId): array
    {
        $offers = Offer::where('team_id', $teamId)->pluck('id');
        
        $stats = [
            'total' => Coupon::whereIn('offer_id', $offers)->count(),
            'active' => Coupon::whereIn('offer_id', $offers)->where('status', 'active')->count(),
            'used' => Coupon::whereIn('offer_id', $offers)->where('status', 'used')->count(),
            'expired' => Coupon::whereIn('offer_id', $offers)->where('status', 'expired')->count(),
            'by_offer' => Coupon::selectRaw('offer_id, count(*) as count')
                ->whereIn('offer_id', $offers)
                ->groupBy('offer_id')
                ->with('offer:id,title')
                ->get(),
            'recent_activity' => Coupon::with(['offer', 'client'])
                ->whereIn('offer_id', $offers)
                ->whereNotNull('redeemed_at')
                ->orderBy('redeemed_at', 'desc')
                ->limit(10)
                ->get(),
            'conversion_rate' => 0
        ];
        
        // Calculate conversion rate
        if ($stats['total'] > 0) {
            $stats['conversion_rate'] = round(($stats['used'] / $stats['total']) * 100, 2);
        }
        
        return $stats;
    }
    
    /**
     * Get coupon usage by platform
     *
     * @param int $teamId
     * @return array
     */
    public function getCouponUsageByPlatform(int $teamId): array
    {
        $offers = Offer::where('team_id', $teamId)->pluck('id');
        
        $usage = Coupon::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "used" THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired
            ')
            ->whereIn('offer_id', $offers)
            ->first()
            ->toArray();
            
        return $usage;
    }
    
    /**
     * Get coupon usage by client segment
     *
     * @param int $teamId
     * @return array
     */
    public function getCouponUsageBySegment(int $teamId): array
    {
        $offers = Offer::where('team_id', $teamId)->pluck('id');
        
        $usage = DB::table('coupons')
            ->join('clients', 'coupons.client_id', '=', 'clients.id')
            ->join('client_segment', 'clients.id', '=', 'client_segment.client_id')
            ->join('audience_clusters', 'client_segment.audience_cluster_id', '=', 'audience_clusters.id')
            ->whereIn('coupons.offer_id', $offers)
            ->selectRaw('
                audience_clusters.name as segment_name,
                COUNT(*) as total,
                SUM(CASE WHEN coupons.status = "used" THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN coupons.status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN coupons.status = "expired" THEN 1 ELSE 0 END) as expired
            ')
            ->groupBy('audience_clusters.id', 'audience_clusters.name')
            ->get()
            ->toArray();
            
        return $usage;
    }
    
    /**
     * Get coupon usage by persona
     *
     * @param int $teamId
     * @return array
     */
    public function getCouponUsageByPersona(int $teamId): array
    {
        $offers = Offer::where('team_id', $teamId)->pluck('id');
        
        $usage = DB::table('coupons')
            ->join('clients', 'coupons.client_id', '=', 'clients.id')
            ->join('client_persona', 'clients.id', '=', 'client_persona.client_id')
            ->join('audience_personas', 'client_persona.audience_persona_id', '=', 'audience_personas.id')
            ->whereIn('coupons.offer_id', $offers)
            ->selectRaw('
                audience_personas.name as persona_name,
                COUNT(*) as total,
                SUM(CASE WHEN coupons.status = "used" THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN coupons.status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN coupons.status = "expired" THEN 1 ELSE 0 END) as expired
            ')
            ->groupBy('audience_personas.id', 'audience_personas.name')
            ->get()
            ->toArray();
            
        return $usage;
    }
    
    /**
     * Get coupon usage over time
     *
     * @param int $teamId
     * @param int $days
     * @return array
     */
    public function getCouponUsageOverTime(int $teamId, int $days = 30): array
    {
        $offers = Offer::where('team_id', $teamId)->pluck('id');
        
        $startDate = now()->subDays($days);
        
        $usage = DB::table('coupons')
            ->whereIn('offer_id', $offers)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = "used" THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
            
        return $usage;
    }
} 