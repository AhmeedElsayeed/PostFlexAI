<?php

namespace App\Services;

use App\Models\ContentRecycle;
use App\Models\Post;
use App\Models\RecycledMedia;
use App\Models\User;
use App\Services\AI\OpenAIService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentRecycleService
{
    protected $openAIService;
    protected $postInsightService;
    protected $schedulerService;

    /**
     * Constructor.
     *
     * @param OpenAIService $openAIService
     * @param PostInsightService $postInsightService
     * @param SchedulerService $schedulerService
     */
    public function __construct(
        OpenAIService $openAIService,
        PostInsightService $postInsightService,
        SchedulerService $schedulerService
    ) {
        $this->openAIService = $openAIService;
        $this->postInsightService = $postInsightService;
        $this->schedulerService = $schedulerService;
    }

    /**
     * Suggest posts for recycling based on performance.
     *
     * @param int $teamId
     * @param int $limit
     * @return array
     */
    public function suggestPostsForRecycling(int $teamId, int $limit = 10): array
    {
        // Get posts with low performance
        $lowPerformancePosts = $this->postInsightService->getLowPerformancePosts($teamId, $limit);
        
        // Get posts with high performance that could be reused
        $highPerformancePosts = $this->postInsightService->getHighPerformancePosts($teamId, $limit);
        
        // Get posts that were published at non-optimal times
        $nonOptimalTimePosts = $this->postInsightService->getPostsPublishedAtNonOptimalTimes($teamId, $limit);
        
        return [
            'low_performance' => $lowPerformancePosts,
            'high_performance' => $highPerformancePosts,
            'non_optimal_time' => $nonOptimalTimePosts
        ];
    }

    /**
     * Generate a recycled version of a post.
     *
     * @param Post $post
     * @param string $strategy
     * @param User $user
     * @return ContentRecycle
     */
    public function generateRecycledContent(Post $post, string $strategy, User $user): ContentRecycle
    {
        // Get post insights
        $insights = $this->postInsightService->getPostInsights($post->id);
        
        // Generate new caption based on strategy
        $newCaption = $this->generateNewCaption($post, $strategy, $insights);
        
        // Suggest new schedule time
        $newSchedule = $this->suggestNewScheduleTime($post, $insights);
        
        // Calculate AI score
        $aiScore = $this->calculateAiScore($newCaption, $insights);
        
        // Create content recycle
        $contentRecycle = new ContentRecycle([
            'post_id' => $post->id,
            'created_by' => $user->id,
            'type' => 'auto',
            'strategy' => $strategy,
            'new_caption' => $newCaption,
            'new_schedule' => $newSchedule,
            'ai_score' => $aiScore,
            'is_approved' => false,
            'performance_metrics' => $insights,
            'ai_suggestions' => $this->generateAiSuggestions($post, $insights)
        ]);
        
        $contentRecycle->save();
        
        // Handle media recycling
        $this->handleMediaRecycling($contentRecycle, $post);
        
        return $contentRecycle;
    }

    /**
     * Generate a new caption for a post.
     *
     * @param Post $post
     * @param string $strategy
     * @param array $insights
     * @return string
     */
    protected function generateNewCaption(Post $post, string $strategy, array $insights): string
    {
        $prompt = $this->buildCaptionPrompt($post, $strategy, $insights);
        
        try {
            $response = $this->openAIService->generateText($prompt);
            return $response['text'] ?? $post->caption;
        } catch (\Exception $e) {
            Log::error('Failed to generate new caption: ' . $e->getMessage());
            return $post->caption;
        }
    }

    /**
     * Build a prompt for caption generation.
     *
     * @param Post $post
     * @param string $strategy
     * @param array $insights
     * @return string
     */
    protected function buildCaptionPrompt(Post $post, string $strategy, array $insights): string
    {
        $prompt = "قم بتحسين النص التالي للمنشور على وسائل التواصل الاجتماعي:\n\n";
        $prompt .= $post->caption . "\n\n";
        
        if ($strategy === 'performance_improvement') {
            $prompt .= "المنشور الأصلي لم يحقق أداءً جيداً. قم بتحسينه مع الحفاظ على نفس الرسالة الأساسية.\n";
            $prompt .= "استخدم لغة أكثر جاذبية وإضافة دعوة للعمل قوية.\n";
        } elseif ($strategy === 'time_change') {
            $prompt .= "قم بتحسين النص ليكون مناسباً للنشر في وقت مختلف من اليوم.\n";
            $prompt .= "أضف عناصر تجعله أكثر ملاءمة للتفاعل في الوقت المقترح.\n";
        } elseif ($strategy === 'similar_content_reuse') {
            $prompt .= "قم بإعادة صياغة النص بطريقة جديدة مع الحفاظ على نفس الموضوع.\n";
            $prompt .= "استخدم نهجاً مختلفاً في تقديم المعلومات.\n";
        }
        
        $prompt .= "\nقم بتوليد نسخة محسنة من النص.";
        
        return $prompt;
    }

    /**
     * Suggest a new schedule time for a post.
     *
     * @param Post $post
     * @param array $insights
     * @return Carbon|null
     */
    protected function suggestNewScheduleTime(Post $post, array $insights): ?Carbon
    {
        try {
            // Get optimal posting times for the team
            $optimalTimes = $this->schedulerService->getOptimalPostingTimes($post->team_id);
            
            if (empty($optimalTimes)) {
                return null;
            }
            
            // Get the next optimal time
            $nextOptimalTime = $this->schedulerService->getNextOptimalTime($optimalTimes);
            
            return $nextOptimalTime;
        } catch (\Exception $e) {
            Log::error('Failed to suggest new schedule time: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate AI score for the recycled content.
     *
     * @param string $newCaption
     * @param array $insights
     * @return float
     */
    protected function calculateAiScore(string $newCaption, array $insights): float
    {
        try {
            $prompt = "قم بتقييم جودة النص التالي للمنشور على وسائل التواصل الاجتماعي على مقياس من 0 إلى 5:\n\n";
            $prompt .= $newCaption . "\n\n";
            $prompt .= "قم بتقييم: الجاذبية، الوضوح، الدعوة للعمل، والتفاعل المحتمل.";
            
            $response = $this->openAIService->generateText($prompt);
            
            // Extract score from response
            if (preg_match('/(\d+(\.\d+)?)/', $response['text'], $matches)) {
                $score = (float) $matches[1];
                return min(5, max(0, $score)); // Ensure score is between 0 and 5
            }
            
            return 3.0; // Default score
        } catch (\Exception $e) {
            Log::error('Failed to calculate AI score: ' . $e->getMessage());
            return 3.0; // Default score
        }
    }

    /**
     * Generate AI suggestions for improving the post.
     *
     * @param Post $post
     * @param array $insights
     * @return array
     */
    protected function generateAiSuggestions(Post $post, array $insights): array
    {
        try {
            $prompt = "قم بتحليل المنشور التالي وتقديم 3 اقتراحات محددة لتحسينه:\n\n";
            $prompt .= $post->caption . "\n\n";
            $prompt .= "المنشور حقق الأداء التالي:\n";
            $prompt .= json_encode($insights, JSON_UNESCAPED_UNICODE) . "\n\n";
            $prompt .= "قدم 3 اقتراحات محددة لتحسين المنشور.";
            
            $response = $this->openAIService->generateText($prompt);
            
            // Parse suggestions from response
            $suggestions = [];
            $lines = explode("\n", $response['text']);
            
            foreach ($lines as $line) {
                if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                    $suggestions[] = $matches[1];
                }
            }
            
            return $suggestions;
        } catch (\Exception $e) {
            Log::error('Failed to generate AI suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Handle media recycling for a content recycle.
     *
     * @param ContentRecycle $contentRecycle
     * @param Post $post
     * @return void
     */
    protected function handleMediaRecycling(ContentRecycle $contentRecycle, Post $post): void
    {
        foreach ($post->media as $media) {
            // For now, just reuse the media
            $recycledMedia = new RecycledMedia([
                'content_recycle_id' => $contentRecycle->id,
                'original_media_id' => $media->id,
                'action' => 'reuse'
            ]);
            
            $recycledMedia->save();
        }
    }

    /**
     * Approve a content recycle.
     *
     * @param ContentRecycle $contentRecycle
     * @return bool
     */
    public function approveContentRecycle(ContentRecycle $contentRecycle): bool
    {
        $contentRecycle->is_approved = true;
        return $contentRecycle->save();
    }

    /**
     * Schedule a recycled content.
     *
     * @param ContentRecycle $contentRecycle
     * @param Carbon $scheduleTime
     * @return bool
     */
    public function scheduleRecycledContent(ContentRecycle $contentRecycle, Carbon $scheduleTime): bool
    {
        if (!$contentRecycle->isApproved()) {
            return false;
        }
        
        $contentRecycle->new_schedule = $scheduleTime;
        return $contentRecycle->save();
    }

    /**
     * Get recycling statistics.
     *
     * @param int $teamId
     * @return array
     */
    public function getRecyclingStats(int $teamId): array
    {
        $stats = [
            'total_recycles' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })->count(),
            'approved_recycles' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })->approved()->count(),
            'auto_recycles' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })->auto()->count(),
            'manual_recycles' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })->manual()->count(),
            'high_score_recycles' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })->highScore()->count(),
            'by_strategy' => [
                'performance_improvement' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                    $query->where('team_id', $teamId);
                })->byStrategy('performance_improvement')->count(),
                'time_change' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                    $query->where('team_id', $teamId);
                })->byStrategy('time_change')->count(),
                'similar_content_reuse' => ContentRecycle::whereHas('post', function ($query) use ($teamId) {
                    $query->where('team_id', $teamId);
                })->byStrategy('similar_content_reuse')->count()
            ]
        ];
        
        return $stats;
    }

    /**
     * Compare performance of original and recycled content.
     *
     * @param ContentRecycle $contentRecycle
     * @return array
     */
    public function comparePerformance(ContentRecycle $contentRecycle): array
    {
        $originalPost = $contentRecycle->post;
        $originalInsights = $this->postInsightService->getPostInsights($originalPost->id);
        
        // Get the recycled post if it exists
        $recycledPost = Post::where('recycled_from_id', $originalPost->id)->first();
        $recycledInsights = $recycledPost ? $this->postInsightService->getPostInsights($recycledPost->id) : null;
        
        return [
            'original' => [
                'post' => $originalPost,
                'insights' => $originalInsights
            ],
            'recycled' => $recycledPost ? [
                'post' => $recycledPost,
                'insights' => $recycledInsights
            ] : null,
            'improvement' => $recycledInsights ? $this->calculateImprovement($originalInsights, $recycledInsights) : null
        ];
    }

    /**
     * Calculate improvement percentage between original and recycled content.
     *
     * @param array $originalInsights
     * @param array $recycledInsights
     * @return array
     */
    protected function calculateImprovement(array $originalInsights, array $recycledInsights): array
    {
        $improvement = [];
        
        $metrics = ['likes', 'comments', 'shares', 'reach', 'engagement_rate'];
        
        foreach ($metrics as $metric) {
            if (isset($originalInsights[$metric]) && isset($recycledInsights[$metric]) && $originalInsights[$metric] > 0) {
                $improvement[$metric] = (($recycledInsights[$metric] - $originalInsights[$metric]) / $originalInsights[$metric]) * 100;
            } else {
                $improvement[$metric] = 0;
            }
        }
        
        return $improvement;
    }
} 