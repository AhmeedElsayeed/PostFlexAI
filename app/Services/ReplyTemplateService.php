<?php

namespace App\Services;

use App\Models\ReplyTemplate;
use App\Models\InboxMessage;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Collection;
use OpenAI\Client;

class ReplyTemplateService
{
    protected $openai;

    public function __construct(Client $openai)
    {
        $this->openai = $openai;
    }

    public function suggestTemplate(InboxMessage $message): ?ReplyTemplate
    {
        // تحليل محتوى الرسالة
        $analysis = $this->analyzeMessage($message);
        
        // البحث عن القوالب المناسبة
        $templates = ReplyTemplate::query()
            ->where('team_id', $message->team_id)
            ->where('is_active', true)
            ->when($analysis['tags'], function ($query, $tags) {
                return $query->whereJsonContains('tags', $tags);
            })
            ->when($analysis['tone'], function ($query, $tone) {
                return $query->where('tone', $tone);
            })
            ->orderBy('usage_count', 'desc')
            ->take(3)
            ->get();

        // إذا لم نجد قوالب مناسبة، نقترح إنشاء قالب جديد
        if ($templates->isEmpty()) {
            return $this->generateNewTemplate($message);
        }

        // اختيار أفضل قالب
        return $this->rankTemplates($templates, $analysis)->first();
    }

    public function improveReply(string $content, string $tone = 'professional'): string
    {
        $prompt = "قم بتحسين هذا الرد بنبرة {$tone}:\n\n{$content}";
        
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'أنت مساعد متخصص في تحسين الردود للتواصل مع العملاء.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);

        return $response->choices[0]->message->content;
    }

    public function generateNewTemplate(InboxMessage $message): ReplyTemplate
    {
        $prompt = "قم بإنشاء قالب رد احترافي للرسالة التالية:\n\n{$message->content}";
        
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'أنت مساعد متخصص في إنشاء قوالب ردود احترافية.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);

        $suggestion = $response->choices[0]->message->content;
        $analysis = $this->analyzeMessage($message);

        return ReplyTemplate::create([
            'team_id' => $message->team_id,
            'title' => 'رد تلقائي: ' . substr($message->content, 0, 50),
            'content' => $suggestion,
            'tags' => $analysis['tags'],
            'tone' => $analysis['tone'],
            'is_active' => false, // يحتاج لموافقة قبل التفعيل
            'metadata' => ['generated_from' => $message->id]
        ]);
    }

    protected function analyzeMessage(InboxMessage $message): array
    {
        $prompt = "قم بتحليل هذه الرسالة وقدم:\n" .
                 "1. التصنيفات المناسبة\n" .
                 "2. نبرة الرد المناسبة\n" .
                 "3. مستوى الأولوية\n\n" .
                 "الرسالة: {$message->content}";

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'أنت محلل متخصص في تصنيف رسائل العملاء.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 200
        ]);

        // تحليل الرد وتحويله لبيانات منظمة
        $analysis = json_decode($response->choices[0]->message->content, true);

        return [
            'tags' => $analysis['tags'] ?? [],
            'tone' => $analysis['tone'] ?? 'professional',
            'priority' => $analysis['priority'] ?? 'normal'
        ];
    }

    protected function rankTemplates(Collection $templates, array $analysis): Collection
    {
        return $templates->sortByDesc(function ($template) use ($analysis) {
            $score = 0;
            
            // نقاط للتطابق في التصنيفات
            $matchingTags = array_intersect($template->tags ?? [], $analysis['tags'] ?? []);
            $score += count($matchingTags) * 2;
            
            // نقاط للتطابق في النبرة
            if ($template->tone === $analysis['tone']) {
                $score += 3;
            }
            
            // نقاط لمعدل الاستخدام
            $score += min($template->usage_count / 10, 5);
            
            // نقاط إضافية للقوالب المميزة بنجمة
            if ($template->is_starred) {
                $score += 2;
            }
            
            return $score;
        });
    }

    public function getTemplateStats(Team $team): array
    {
        $templates = ReplyTemplate::where('team_id', $team->id);
        
        return [
            'total_templates' => $templates->count(),
            'total_usage' => $templates->sum('usage_count'),
            'most_used' => $templates->orderBy('usage_count', 'desc')->take(5)->get(),
            'by_platform' => ReplyTemplateUsage::where('team_id', $team->id)
                ->selectRaw('platform, count(*) as count')
                ->groupBy('platform')
                ->get(),
            'by_user' => ReplyTemplateUsage::where('team_id', $team->id)
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->with('user:id,name')
                ->get()
        ];
    }
} 