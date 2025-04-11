<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    protected $fillable = [
        'name',
        'ad_set_id',
        'campaign_id',
        'ad_account_id',
        'status',
        'creative_type',
        'creative_id',
        'creative_url',
        'creative_title',
        'creative_description',
        'creative_image_url',
        'creative_video_url',
        'call_to_action',
        'landing_page_url',
        'tracking_specs',
        'bid_amount',
        'bid_type',
        'targeting',
        'placement'
    ];

    protected $casts = [
        'tracking_specs' => 'array',
        'targeting' => 'array',
        'placement' => 'array',
        'bid_amount' => 'decimal:2'
    ];

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
} 