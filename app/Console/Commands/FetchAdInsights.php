<?php

namespace App\Console\Commands;

use App\Services\AdInsightService;
use Illuminate\Console\Command;

class FetchAdInsights extends Command
{
    protected $signature = 'ad-insights:fetch {--account=} {--days=30}';
    protected $description = 'Fetch ad insights for all ads or a specific account';

    protected $adInsightService;

    public function __construct(AdInsightService $adInsightService)
    {
        parent::__construct();
        $this->adInsightService = $adInsightService;
    }

    public function handle()
    {
        $accountId = $this->option('account');
        $days = $this->option('days');

        $this->info('Starting to fetch ad insights...');

        try {
            $result = $this->adInsightService->fetchAndUpdateInsights(
                $accountId,
                now()->subDays($days)->format('Y-m-d'),
                now()->format('Y-m-d')
            );

            $this->info('Successfully fetched ' . count($result) . ' ad insights.');
        } catch (\Exception $e) {
            $this->error('Failed to fetch ad insights: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 