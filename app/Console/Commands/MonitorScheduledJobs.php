<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\JobFailedNotification;
use Illuminate\Support\Facades\DB;

class MonitorScheduledJobs extends Command
{
    protected $signature = 'jobs:monitor';
    protected $description = 'Monitor the status of scheduled jobs';

    public function handle()
    {
        $this->checkFailedJobs();
        $this->checkStuckJobs();
        $this->checkJobPerformance();
    }

    private function checkFailedJobs()
    {
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->get();

        if ($failedJobs->isNotEmpty()) {
            foreach ($failedJobs as $job) {
                Log::error("Failed job detected: {$job->id}");
                Notification::route('mail', config('app.admin_email'))
                    ->notify(new JobFailedNotification($job));
            }
        }
    }

    private function checkStuckJobs()
    {
        $stuckJobs = DB::table('jobs')
            ->where('attempts', '>', 3)
            ->where('created_at', '<', now()->subHour())
            ->get();

        if ($stuckJobs->isNotEmpty()) {
            foreach ($stuckJobs as $job) {
                Log::warning("Stuck job detected: {$job->id}");
                // Optionally restart the job or notify admin
            }
        }
    }

    private function checkJobPerformance()
    {
        $jobs = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as total'), 
                    DB::raw('avg(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as avg_time'))
            ->where('processed_at', '>=', now()->subDay())
            ->groupBy('queue')
            ->get();

        foreach ($jobs as $job) {
            if ($job->avg_time > 300) { // More than 5 minutes
                Log::warning("Slow job detected in queue {$job->queue}: {$job->avg_time} seconds average");
            }
        }
    }
} 