<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Run token refresh job every hour
        $schedule->job(new \App\Jobs\RefreshSocialAccountTokens)->hourly();
        
        // Publish scheduled posts every minute
        $schedule->command('posts:publish-scheduled')->everyMinute();
        
        // Fetch messages from platforms every 5 minutes
        $schedule->command('fetch:platform-messages')->everyFiveMinutes();

        // Fetch post insights every hour
        $schedule->job(new \App\Jobs\FetchPostInsights)->hourly();

        // Fetch account stats every 6 hours
        $schedule->job(new \App\Jobs\FetchAccountStats)->everySixHours();

        // Daily database backup at 2 AM
        $schedule->command('backup:database')
                ->dailyAt('02:00')
                ->withoutOverlapping();

        // Fetch audience insights weekly
        $schedule->job(new \App\Jobs\FetchAudienceInsights)
                ->weekly()
                ->withoutOverlapping();

        // Retrain AI models weekly
        $schedule->command('ai:retrain')
                ->weekly()
                ->withoutOverlapping()
                ->runInBackground();

        // Fetch ad insights daily at midnight
        $schedule->command('ad-insights:fetch')
            ->daily()
            ->at('00:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/ad-insights.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 