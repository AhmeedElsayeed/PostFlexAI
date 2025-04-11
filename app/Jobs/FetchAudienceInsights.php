<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\AudienceAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchAudienceInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;

    public function __construct(SocialAccount $account)
    {
        $this->account = $account;
    }

    public function handle(AudienceAnalysisService $audienceAnalysisService)
    {
        try {
            $audienceAnalysisService->analyzeAccount($this->account);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch audience insights for account ' . $this->account->id, [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('Audience insights job failed for account ' . $this->account->id, [
            'error' => $exception->getMessage()
        ]);
    }
} 