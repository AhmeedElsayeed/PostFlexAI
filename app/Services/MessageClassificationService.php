<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageLabel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageClassificationService
{
    protected array $labels = [
        'استفسار',
        'شكوى',
        'اقتراح',
        'طلب حجز',
        'لم يتم الرد',
        'تم الرد والمتابعة',
        'طلب منتج / سعر',
        'رسالة غاضبة',
        'رسالة غير مفيدة'
    ];

    public function classifyMessage(Message $message): MessageLabel
    {
        try {
            $label = $this->analyzeText($message->content);
            
            return MessageLabel::create([
                'message_id' => $message->id,
                'label' => $label,
                'source' => 'auto'
            ]);
        } catch (\Exception $e) {
            Log::error('Message classification failed: ' . $e->getMessage());
            
            // Default to 'لم يتم الرد' if classification fails
            return MessageLabel::create([
                'message_id' => $message->id,
                'label' => 'لم يتم الرد',
                'source' => 'auto'
            ]);
        }
    }

    protected function analyzeText(string $text): string
    {
        // TODO: Replace with actual AI service integration
        // For now, using a simple keyword-based classification
        $text = mb_strtolower($text);
        
        if (str_contains($text, 'شكوى') || str_contains($text, 'مشكلة') || str_contains($text, 'سيء')) {
            return 'شكوى';
        }
        
        if (str_contains($text, 'كم') || str_contains($text, 'سعر') || str_contains($text, 'تكلفة')) {
            return 'طلب منتج / سعر';
        }
        
        if (str_contains($text, 'احجز') || str_contains($text, 'حجز') || str_contains($text, 'موعد')) {
            return 'طلب حجز';
        }
        
        if (str_contains($text, 'اقتراح') || str_contains($text, 'فكرة')) {
            return 'اقتراح';
        }
        
        if (str_contains($text, 'غاضب') || str_contains($text, 'غضب') || str_contains($text, 'سيء')) {
            return 'رسالة غاضبة';
        }
        
        return 'استفسار';
    }

    public function getAvailableLabels(): array
    {
        return $this->labels;
    }
} 