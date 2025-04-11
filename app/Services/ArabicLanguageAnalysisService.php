<?php

namespace App\Services;

use App\Models\CustomSentimentWord;

class ArabicLanguageAnalysisService
{
    private array $arabicSentimentWords = [
        'positive' => [
            'رائع', 'ممتاز', 'جميل', 'سعيد', 'مشكور', 'شكراً', 'ممتاز', 'رائع', 'سعيد',
            'ممتاز', 'رائع', 'سعيد', 'ممتاز', 'رائع', 'سعيد', 'ممتاز', 'رائع', 'سعيد'
        ],
        'negative' => [
            'سيء', 'رديء', 'زفت', 'مخربق', 'مضيعة', 'نص', 'نص نص', 'نص نص نص', 'نص نص نص نص',
            'نص نص نص نص نص', 'نص نص نص نص نص نص', 'نص نص نص نص نص نص نص', 'نص نص نص نص نص نص نص نص'
        ]
    ];

    private array $arabicIntentPatterns = [
        'inquiry' => [
            'كيف', 'متى', 'أين', 'كم', 'لماذا', 'ما هو', 'ما هي', 'ما هي', 'ما هي', 'ما هي',
            'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي'
        ],
        'complaint' => [
            'شكوى', 'مشكلة', 'عطل', 'تأخير', 'خطأ', 'غلط', 'سيء', 'رديء', 'زفت', 'مخربق',
            'مضيعة', 'نص', 'نص نص', 'نص نص نص', 'نص نص نص نص', 'نص نص نص نص نص'
        ],
        'booking' => [
            'حجز', 'موعد', 'توقيت', 'متى', 'أين', 'كم', 'لماذا', 'ما هو', 'ما هي', 'ما هي',
            'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي', 'ما هي'
        ],
        'product_request' => [
            'منتج', 'سلعة', 'خدمة', 'سعر', 'متوفر', 'متوفر', 'متوفر', 'متوفر', 'متوفر',
            'متوفر', 'متوفر', 'متوفر', 'متوفر', 'متوفر', 'متوفر', 'متوفر', 'متوفر', 'متوفر'
        ],
        'anger' => [
            'غضب', 'غاضب', 'غاضبة', 'غاضبين', 'غاضبات', 'غاضبون', 'غاضبات', 'غاضبون',
            'غاضبات', 'غاضبون', 'غاضبات', 'غاضبون', 'غاضبات', 'غاضبون', 'غاضبات', 'غاضبون'
        ]
    ];

    private array $arabicKeywords = [
        'price' => ['سعر', 'تكلفة', 'ثمن', 'بكم', 'كم السعر', 'كم التكلفة', 'كم الثمن'],
        'availability' => ['متوفر', 'موجود', 'غير متوفر', 'غير موجود', 'نفذ', 'نفذت'],
        'delivery' => ['توصيل', 'شحن', 'تسليم', 'موعد التسليم', 'موعد التوصيل', 'موعد الشحن'],
        'quality' => ['جودة', 'نوعية', 'ممتاز', 'رديء', 'سيء', 'زفت', 'مخربق', 'مضيعة'],
        'service' => ['خدمة', 'خدمات', 'خدمة عملاء', 'خدمة العملاء', 'خدمة العملاء', 'خدمة العملاء']
    ];

    public function analyzeSentiment(string $text): string
    {
        $positiveCount = 0;
        $negativeCount = 0;

        // Check default sentiment words
        foreach ($this->arabicSentimentWords['positive'] as $word) {
            if (stripos($text, $word) !== false) {
                $positiveCount++;
            }
        }

        foreach ($this->arabicSentimentWords['negative'] as $word) {
            if (stripos($text, $word) !== false) {
                $negativeCount++;
            }
        }

        // Check custom sentiment words
        $customPositiveWords = CustomSentimentWord::active()->positive()->pluck('word')->toArray();
        $customNegativeWords = CustomSentimentWord::active()->negative()->pluck('word')->toArray();

        foreach ($customPositiveWords as $word) {
            if (stripos($text, $word) !== false) {
                $positiveCount++;
            }
        }

        foreach ($customNegativeWords as $word) {
            if (stripos($text, $word) !== false) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    public function detectIntent(string $text): string
    {
        $intentScores = [
            'inquiry' => 0,
            'complaint' => 0,
            'booking' => 0,
            'product_request' => 0,
            'anger' => 0
        ];

        foreach ($this->arabicIntentPatterns as $intent => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($text, $pattern) !== false) {
                    $intentScores[$intent]++;
                }
            }
        }

        arsort($intentScores);
        return key($intentScores);
    }

    public function extractKeywords(string $text): array
    {
        $keywords = [];
        foreach ($this->arabicKeywords as $category => $words) {
            foreach ($words as $word) {
                if (stripos($text, $word) !== false) {
                    $keywords[] = $word;
                }
            }
        }
        return array_unique($keywords);
    }

    public function getPriority(string $text): string
    {
        $urgencyWords = ['مهم', 'عاجل', 'فوري', 'سريع', 'مباشر', 'الآن', 'اليوم', 'غداً'];
        $highPriorityCount = 0;

        foreach ($urgencyWords as $word) {
            if (stripos($text, $word) !== false) {
                $highPriorityCount++;
            }
        }

        if ($highPriorityCount >= 2) {
            return 'high';
        } elseif ($highPriorityCount === 1) {
            return 'medium';
        }

        return 'low';
    }
} 