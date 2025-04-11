<?php

namespace App\Http\Controllers;

use App\Models\ReplyTemplate;
use App\Models\InboxMessage;
use App\Services\ReplyTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReplyTemplateController extends Controller
{
    protected $replyTemplateService;

    public function __construct(ReplyTemplateService $replyTemplateService)
    {
        $this->replyTemplateService = $replyTemplateService;
    }

    public function index(Request $request)
    {
        $query = ReplyTemplate::query()
            ->where('team_id', Auth::user()->team_id)
            ->when($request->search, function ($q, $search) {
                return $q->search($search);
            })
            ->when($request->tag, function ($q, $tag) {
                return $q->byTag($tag);
            })
            ->when($request->tone, function ($q, $tone) {
                return $q->byTone($tone);
            })
            ->when($request->starred, function ($q) {
                return $q->starred();
            })
            ->when($request->active, function ($q) {
                return $q->active();
            });

        $templates = $query->with('usages')->paginate(15);

        return response()->json([
            'templates' => $templates,
            'stats' => $this->replyTemplateService->getTemplateStats(Auth::user()->team)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'tone' => 'nullable|string',
            'is_global' => 'boolean'
        ]);

        // التحقق من صلاحية إنشاء قوالب عامة
        if ($validated['is_global'] ?? false) {
            Gate::authorize('create-global-templates');
        }

        $template = ReplyTemplate::create([
            'team_id' => Auth::user()->team_id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'tags' => $validated['tags'] ?? [],
            'tone' => $validated['tone'],
            'is_global' => $validated['is_global'] ?? false
        ]);

        return response()->json($template, 201);
    }

    public function update(Request $request, ReplyTemplate $template)
    {
        Gate::authorize('update', $template);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
            'tags' => 'array',
            'tone' => 'string',
            'is_active' => 'boolean',
            'is_global' => 'boolean'
        ]);

        // التحقق من صلاحية تحديث القوالب العامة
        if (isset($validated['is_global']) && $validated['is_global']) {
            Gate::authorize('manage-global-templates');
        }

        $template->update($validated);

        return response()->json($template);
    }

    public function destroy(ReplyTemplate $template)
    {
        Gate::authorize('delete', $template);

        $template->delete();

        return response()->noContent();
    }

    public function use(Request $request, ReplyTemplate $template, InboxMessage $message)
    {
        Gate::authorize('use', $template);

        $validated = $request->validate([
            'customization' => 'nullable|array',
            'platform' => 'required|string'
        ]);

        // تخصيص القالب
        $content = $template->customize($validated['customization'] ?? []);

        // تسجيل الاستخدام
        $template->incrementUsage(
            Auth::user(),
            $message,
            $validated['platform'],
            $validated['customization'] ?? null
        );

        return response()->json([
            'content' => $content
        ]);
    }

    public function toggleStar(ReplyTemplate $template)
    {
        Gate::authorize('update', $template);

        $template->toggleStar();

        return response()->json($template);
    }

    public function suggest(InboxMessage $message)
    {
        Gate::authorize('view', $message);

        $suggestion = $this->replyTemplateService->suggestTemplate($message);

        return response()->json($suggestion);
    }

    public function improve(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'tone' => 'nullable|string|in:professional,friendly,formal,casual'
        ]);

        $improved = $this->replyTemplateService->improveReply(
            $validated['content'],
            $validated['tone'] ?? 'professional'
        );

        return response()->json([
            'improved_content' => $improved
        ]);
    }

    public function stats(Request $request)
    {
        $stats = $this->replyTemplateService->getTemplateStats(Auth::user()->team);
        
        return response()->json($stats);
    }
} 