<?php

namespace App\Providers;

use App\Models\MediaItem;
use App\Policies\MediaItemPolicy;
use App\Models\ApiKey;
use App\Policies\ApiKeyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        MediaItem::class => MediaItemPolicy::class,
        ApiKey::class => ApiKeyPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
} 