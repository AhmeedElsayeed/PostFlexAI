<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageLabel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AdvancedMessageAnalysisService
{
    protected array $sentimentLabels = [
        'positive' => 'إيجابي',
        'negative' => 'سلبي',
        'neutral' => 'محايد'
    ];

    protected array $intentLabels = [
        'inquiry' => 'استفسار',
        'complaint' => 'شكوى',
        'suggestion' => 'اقتراح',
        'booking' => 'طلب حجز',
        'product_request' => 'طلب منتج / سعر',
        'angry' => 'رسالة غاضبة',
        'spam' => 'رسالة غير مفيدة'
    ];

    protected array $priorityLabels = [
        'high' => 'عالية',
        'medium' => 'متوسطة',
        'low' => 'منخفضة'
    ];

    public function analyzeMessage(Message $message): array
    {
        try {
            // Check cache first
            $cacheKey = 'message_analysis_' . $message->id;
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Perform advanced analysis
            $analysis = $this->performAnalysis($message->content);
            
            // Store in cache for 24 hours
            Cache::put($cacheKey, $analysis, now()->addHours(24));
            
            return $analysis;
        } catch (\Exception $e) {
            Log::error('Advanced message analysis failed: ' . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    protected function performAnalysis(string $content): array
    {
        // TODO: Replace with actual AI service integration
        // For now, using a more sophisticated keyword-based approach
        
        $content = mb_strtolower($content);
        
        // Sentiment analysis
        $sentiment = $this->analyzeSentiment($content);
        
        // Intent analysis
        $intent = $this->analyzeIntent($content);
        
        // Priority analysis
        $priority = $this->analyzePriority($content, $sentiment, $intent);
        
        // Keywords extraction
        $keywords = $this->extractKeywords($content);
        
        return [
            'sentiment' => $sentiment,
            'intent' => $intent,
            'priority' => $priority,
            'keywords' => $keywords,
            'confidence' => $this->calculateConfidence($content, $sentiment, $intent)
        ];
    }

    protected function analyzeSentiment(string $content): string
    {
        $positiveWords = ['شكرا', 'رائع', 'ممتاز', 'سعيد', 'سعيدة', 'ممتازة', 'جيد', 'جيدة', 'ممتاز', 'ممتازة'];
        $negativeWords = ['سيء', 'سيئة', 'غاضب', 'غاضبة', 'مشكلة', 'مشاكل', 'خطأ', 'أخطاء', 'غير راضي', 'غير راضية'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            if (str_contains($content, $word)) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeWords as $word) {
            if (str_contains($content, $word)) {
                $negativeCount++;
            }
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }

    protected function analyzeIntent(string $content): string
    {
        if (str_contains($content, 'شكوى') || str_contains($content, 'مشكلة') || str_contains($content, 'سيء')) {
            return 'complaint';
        }
        
        if (str_contains($content, 'كم') || str_contains($content, 'سعر') || str_contains($content, 'تكلفة')) {
            return 'product_request';
        }
        
        if (str_contains($content, 'احجز') || str_contains($content, 'حجز') || str_contains($content, 'موعد')) {
            return 'booking';
        }
        
        if (str_contains($content, 'اقتراح') || str_contains($content, 'فكرة')) {
            return 'suggestion';
        }
        
        if (str_contains($content, 'غاضب') || str_contains($content, 'غضب') || str_contains($content, 'سيء')) {
            return 'angry';
        }
        
        if (str_contains($content, 'شراء', 'بيع', 'تخفيض', 'عرض', 'كوبون', 'كوبونات')) {
            return 'spam';
        }
        
        return 'inquiry';
    }

    protected function analyzePriority(string $content, string $sentiment, string $intent): string
    {
        // High priority for negative sentiment or complaints
        if ($sentiment === 'negative' || $intent === 'complaint' || $intent === 'angry') {
            return 'high';
        }
        
        // Medium priority for product requests or booking
        if ($intent === 'product_request' || $intent === 'booking') {
            return 'medium';
        }
        
        // Low priority for everything else
        return 'low';
    }

    protected function extractKeywords(string $content): array
    {
        // Simple keyword extraction based on frequency
        $words = explode(' ', $content);
        $wordCount = [];
        
        foreach ($words as $word) {
            if (mb_strlen($word) > 2) { // Ignore very short words
                if (!isset($wordCount[$word])) {
                    $wordCount[$word] = 0;
                }
                $wordCount[$word]++;
            }
        }
        
        arsort($wordCount);
        return array_slice(array_keys($wordCount), 0, 5);
    }

    protected function calculateConfidence(string $content, string $sentiment, string $intent): float
    {
        // Simple confidence calculation based on content length and keyword matches
        $confidence = 0.5; // Base confidence
        
        // Longer messages tend to be more clear
        $confidence += min(0.2, mb_strlen($content) / 1000);
        
        // Adjust based on sentiment and intent
        if ($sentiment === 'negative' || $intent === 'complaint') {
            $confidence += 0.1; // These are usually more clear
        }
        
        return min(1.0, $confidence);
    }

    protected function getDefaultAnalysis(): array
    {
        return [
            'sentiment' => 'neutral',
            'intent' => 'inquiry',
            'priority' => 'medium',
            'keywords' => [],
            'confidence' => 0.5
        ];
    }

    public function getSentimentLabels(): array
    {
        return $this->sentimentLabels;
    }

    public function getIntentLabels(): array
    {
        return $this->intentLabels;
    }

    public function getPriorityLabels(): array
    {
        return $this->priorityLabels;
    }
} 