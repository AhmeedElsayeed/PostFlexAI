<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Coupon;
use App\Models\Client;
use App\Models\AudiencePersona;
use App\Models\AudienceCluster;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OfferService
{
    /**
     * Create a new offer
     *
     * @param array $data
     * @param int $teamId
     * @return Offer
     */
    public function createOffer(array $data, int $teamId): Offer
    {
        $data['team_id'] = $teamId;
        
        // Convert target personas and segments to JSON if they're arrays
        if (isset($data['target_personas']) && is_array($data['target_personas'])) {
            $data['target_personas'] = json_encode($data['target_personas']);
        }
        
        if (isset($data['target_segments']) && is_array($data['target_segments'])) {
            $data['target_segments'] = json_encode($data['target_segments']);
        }
        
        // Convert terms and conditions to JSON if it's an array
        if (isset($data['terms_conditions']) && is_array($data['terms_conditions'])) {
            $data['terms_conditions'] = json_encode($data['terms_conditions']);
        }
        
        return Offer::create($data);
    }
    
    /**
     * Update an existing offer
     *
     * @param Offer $offer
     * @param array $data
     * @return Offer
     */
    public function updateOffer(Offer $offer, array $data): Offer
    {
        // Convert target personas and segments to JSON if they're arrays
        if (isset($data['target_personas']) && is_array($data['target_personas'])) {
            $data['target_personas'] = json_encode($data['target_personas']);
        }
        
        if (isset($data['target_segments']) && is_array($data['target_segments'])) {
            $data['target_segments'] = json_encode($data['target_segments']);
        }
        
        // Convert terms and conditions to JSON if it's an array
        if (isset($data['terms_conditions']) && is_array($data['terms_conditions'])) {
            $data['terms_conditions'] = json_encode($data['terms_conditions']);
        }
        
        $offer->update($data);
        return $offer;
    }
    
    /**
     * Generate coupons for an offer
     *
     * @param Offer $offer
     * @param int $quantity
     * @param int|null $maxUsage
     * @return array
     */
    public function generateCoupons(Offer $offer, int $quantity, ?int $maxUsage = null): array
    {
        $coupons = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            do {
                $code = strtoupper(Str::random(8));
            } while (Coupon::where('code', $code)->exists());
            
            $coupons[] = Coupon::create([
                'offer_id' => $offer->id,
                'code' => $code,
                'max_usage' => $maxUsage
            ]);
        }
        
        return $coupons;
    }
    
    /**
     * Assign coupons to specific clients
     *
     * @param Offer $offer
     * @param array $clientIds
     * @param int|null $maxUsage
     * @return array
     */
    public function assignCouponsToClients(Offer $offer, array $clientIds, ?int $maxUsage = null): array
    {
        $assignedCoupons = [];
        
        foreach ($clientIds as $clientId) {
            // Check if client already has a coupon for this offer
            $existingCoupon = Coupon::where('offer_id', $offer->id)
                ->where('client_id', $clientId)
                ->first();
                
            if ($existingCoupon) {
                $assignedCoupons[] = $existingCoupon;
                continue;
            }
            
            // Generate a unique code
            do {
                $code = strtoupper(Str::random(8));
            } while (Coupon::where('code', $code)->exists());
            
            // Create the coupon
            $coupon = Coupon::create([
                'offer_id' => $offer->id,
                'client_id' => $clientId,
                'code' => $code,
                'max_usage' => $maxUsage
            ]);
            
            $assignedCoupons[] = $coupon;
        }
        
        return $assignedCoupons;
    }
    
    /**
     * Assign an offer to specific personas
     *
     * @param Offer $offer
     * @param array $personaIds
     * @return Offer
     */
    public function assignToPersonas(Offer $offer, array $personaIds): Offer
    {
        $offer->target_personas = $personaIds;
        $offer->save();
        
        return $offer;
    }
    
    /**
     * Assign an offer to specific segments
     *
     * @param Offer $offer
     * @param array $segmentIds
     * @return Offer
     */
    public function assignToSegments(Offer $offer, array $segmentIds): Offer
    {
        $offer->target_segments = $segmentIds;
        $offer->save();
        
        return $offer;
    }
    
    /**
     * Get clients eligible for an offer based on personas and segments
     *
     * @param Offer $offer
     * @return array
     */
    public function getEligibleClients(Offer $offer): array
    {
        $query = Client::query();
        
        // Filter by personas if specified
        if (!empty($offer->target_personas)) {
            $personaIds = is_string($offer->target_personas) 
                ? json_decode($offer->target_personas, true) 
                : $offer->target_personas;
                
            if (!empty($personaIds)) {
                $query->whereHas('personas', function ($q) use ($personaIds) {
                    $q->whereIn('audience_personas.id', $personaIds);
                });
            }
        }
        
        // Filter by segments if specified
        if (!empty($offer->target_segments)) {
            $segmentIds = is_string($offer->target_segments) 
                ? json_decode($offer->target_segments, true) 
                : $offer->target_segments;
                
            if (!empty($segmentIds)) {
                $query->whereHas('segments', function ($q) use ($segmentIds) {
                    $q->whereIn('audience_clusters.id', $segmentIds);
                });
            }
        }
        
        return $query->get()->pluck('id')->toArray();
    }
    
    /**
     * Auto-generate offers based on client behavior
     *
     * @param int $teamId
     * @return array
     */
    public function autoGenerateOffers(int $teamId): array
    {
        // Get active personas
        $personas = AudiencePersona::where('team_id', $teamId)->get();
        
        $generatedOffers = [];
        
        foreach ($personas as $persona) {
            // Skip if no engagement data
            if (empty($persona->engagement_metrics)) {
                continue;
            }
            
            // Determine offer type based on persona characteristics
            $offerType = $this->determineOfferType($persona);
            
            // Determine offer value based on persona value
            $value = $this->determineOfferValue($persona);
            
            // Create the offer
            $offer = Offer::create([
                'team_id' => $teamId,
                'title' => "Auto-generated offer for {$persona->name}",
                'description' => "Special offer tailored for {$persona->name} persona",
                'type' => $offerType,
                'value' => $value,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'is_active' => true,
                'target_personas' => json_encode([$persona->id]),
                'is_auto_generated' => true,
                'ai_recommendations' => json_encode([
                    'reason' => "Generated based on {$persona->name} engagement metrics",
                    'persona_id' => $persona->id
                ])
            ]);
            
            $generatedOffers[] = $offer;
        }
        
        return $generatedOffers;
    }
    
    /**
     * Determine the best offer type for a persona
     *
     * @param AudiencePersona $persona
     * @return string
     */
    private function determineOfferType(AudiencePersona $persona): string
    {
        // Default to discount
        $type = 'discount';
        
        // If persona has high value, offer a bundle
        if (isset($persona->value_score) && $persona->value_score > 8) {
            $type = 'bundle';
        }
        
        // If persona has low engagement, offer a freebie
        if (isset($persona->engagement_metrics['average_engagement_rate']) && 
            $persona->engagement_metrics['average_engagement_rate'] < 0.05) {
            $type = 'freebie';
        }
        
        return $type;
    }
    
    /**
     * Determine the best offer value for a persona
     *
     * @param AudiencePersona $persona
     * @return float
     */
    private function determineOfferValue(AudiencePersona $persona): float
    {
        // Default to 10% discount
        $value = 10.0;
        
        // If persona has high value, offer a higher discount
        if (isset($persona->value_score) && $persona->value_score > 8) {
            $value = 20.0;
        }
        
        // If persona has very high value, offer an even higher discount
        if (isset($persona->value_score) && $persona->value_score > 9) {
            $value = 30.0;
        }
        
        return $value;
    }
    
    /**
     * Get offer statistics
     *
     * @param int $teamId
     * @return array
     */
    public function getOfferStats(int $teamId): array
    {
        $offers = Offer::where('team_id', $teamId)->get();
        
        $stats = [
            'total_offers' => $offers->count(),
            'active_offers' => $offers->where('is_active', true)->count(),
            'expired_offers' => $offers->where('is_active', false)->count(),
            'auto_generated' => $offers->where('is_auto_generated', true)->count(),
            'by_type' => [
                'discount' => $offers->where('type', 'discount')->count(),
                'freebie' => $offers->where('type', 'freebie')->count(),
                'bundle' => $offers->where('type', 'bundle')->count(),
                'other' => $offers->where('type', 'other')->count(),
            ],
            'total_coupons' => 0,
            'active_coupons' => 0,
            'used_coupons' => 0,
            'expired_coupons' => 0,
            'conversion_rate' => 0,
            'top_performing' => []
        ];
        
        // Calculate coupon statistics
        foreach ($offers as $offer) {
            $stats['total_coupons'] += $offer->coupons()->count();
            $stats['active_coupons'] += $offer->getActiveCouponsCount();
            $stats['used_coupons'] += $offer->getUsedCouponsCount();
            $stats['expired_coupons'] += $offer->getExpiredCouponsCount();
            
            // Add to top performing if has good conversion rate
            if ($offer->getConversionRate() > 50) {
                $stats['top_performing'][] = [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'conversion_rate' => $offer->getConversionRate(),
                    'total_coupons' => $offer->coupons()->count(),
                    'used_coupons' => $offer->getUsedCouponsCount()
                ];
            }
        }
        
        // Calculate overall conversion rate
        if ($stats['total_coupons'] > 0) {
            $stats['conversion_rate'] = round(($stats['used_coupons'] / $stats['total_coupons']) * 100, 2);
        }
        
        // Sort top performing by conversion rate
        usort($stats['top_performing'], function ($a, $b) {
            return $b['conversion_rate'] <=> $a['conversion_rate'];
        });
        
        // Limit to top 5
        $stats['top_performing'] = array_slice($stats['top_performing'], 0, 5);
        
        return $stats;
    }
} 