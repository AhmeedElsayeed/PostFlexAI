<?php

namespace App\Services;

use App\Models\Post;
use App\Models\AudienceCluster;
use App\Models\AudiencePersona;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleService
{
    protected $audienceAnalysisService;

    public function __construct(AudienceAnalysisService $audienceAnalysisService)
    {
        $this->audienceAnalysisService = $audienceAnalysisService;
    }

    public function suggestBestTime(Post $post): Carbon
    {
        $targetSegments = $post->targetSegments;
        $targetPersonas = $post->targetPersonas;
        
        if ($targetSegments->isEmpty() && $targetPersonas->isEmpty()) {
            return $this->getDefaultTime();
        }

        $bestTimes = collect();

        // Collect best times from segments
        foreach ($targetSegments as $segment) {
            if (isset($segment->best_posting_time)) {
                $bestTimes->push([
                    'time' => Carbon::parse($segment->best_posting_time),
                    'weight' => $segment->engagement_rate
                ]);
            }
        }

        // Collect best times from personas
        foreach ($targetPersonas as $persona) {
            if (isset($persona->engagement_patterns['best_time'])) {
                $bestTimes->push([
                    'time' => Carbon::parse($persona->engagement_patterns['best_time']),
                    'weight' => $persona->engagement_rate
                ]);
            }
        }

        if ($bestTimes->isEmpty()) {
            return $this->getDefaultTime();
        }

        // Calculate weighted average time
        $totalWeight = $bestTimes->sum('weight');
        $weightedTime = $bestTimes->reduce(function ($carry, $item) use ($totalWeight) {
            return $carry + ($item['time']->timestamp * ($item['weight'] / $totalWeight));
        }, 0);

        return Carbon::createFromTimestamp($weightedTime);
    }

    public function optimizeSchedule(Collection $posts): Collection
    {
        $scheduledPosts = collect();
        $timeSlots = $this->generateTimeSlots();

        foreach ($posts as $post) {
            $bestTime = $this->suggestBestTime($post);
            $optimalSlot = $this->findOptimalSlot($bestTime, $timeSlots);
            
            if ($optimalSlot) {
                $post->scheduled_at = $optimalSlot;
                $scheduledPosts->push($post);
                $timeSlots = $timeSlots->forget($optimalSlot->format('Y-m-d H:i:s'));
            }
        }

        return $scheduledPosts;
    }

    protected function getDefaultTime(): Carbon
    {
        // Default to 10 AM
        return Carbon::now()->setHour(10)->setMinute(0)->setSecond(0);
    }

    protected function generateTimeSlots(): Collection
    {
        $slots = collect();
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->addDays(7)->endOfDay();

        while ($start < $end) {
            // Generate slots every 2 hours
            if ($start->hour >= 8 && $start->hour <= 20) {
                $slots->put($start->format('Y-m-d H:i:s'), $start->copy());
            }
            $start->addHours(2);
        }

        return $slots;
    }

    protected function findOptimalSlot(Carbon $targetTime, Collection $availableSlots): ?Carbon
    {
        if ($availableSlots->isEmpty()) {
            return null;
        }

        // Find the closest available slot
        return $availableSlots->min(function ($slot) use ($targetTime) {
            return abs($slot->diffInMinutes($targetTime));
        });
    }
} 