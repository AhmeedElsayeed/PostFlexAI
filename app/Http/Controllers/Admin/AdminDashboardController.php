<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Feedback;
use App\Models\SystemSetting;
use App\Models\Team;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    protected $adminDashboardService;

    public function __construct(AdminDashboardService $adminDashboardService)
    {
        $this->adminDashboardService = $adminDashboardService;
        $this->middleware(['auth', 'role:super_admin']);
    }

    /**
     * Get dashboard overview statistics.
     */
    public function overview()
    {
        $stats = Cache::remember('admin_dashboard_overview', 300, function () {
            return [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'suspended' => User::where('is_active', false)->count(),
                    'new_today' => User::whereDate('created_at', today())->count(),
                ],
                'teams' => [
                    'total' => Team::count(),
                    'active' => Team::where('is_active', true)->count(),
                    'new_today' => Team::whereDate('created_at', today())->count(),
                ],
                'subscriptions' => [
                    'total' => $this->adminDashboardService->getTotalSubscriptions(),
                    'active' => $this->adminDashboardService->getActiveSubscriptions(),
                    'expired' => $this->adminDashboardService->getExpiredSubscriptions(),
                    'revenue' => [
                        'today' => $this->adminDashboardService->getTodayRevenue(),
                        'month' => $this->adminDashboardService->getMonthRevenue(),
                        'year' => $this->adminDashboardService->getYearRevenue(),
                    ],
                ],
                'feedback' => [
                    'total' => Feedback::count(),
                    'pending' => Feedback::where('status', 'pending')->count(),
                    'in_progress' => Feedback::where('status', 'in_progress')->count(),
                    'resolved' => Feedback::where('status', 'resolved')->count(),
                ],
                'system' => [
                    'jobs' => $this->adminDashboardService->getJobsStatus(),
                    'performance' => $this->adminDashboardService->getSystemPerformance(),
                    'ai_usage' => $this->adminDashboardService->getAiUsageStats(),
                ],
            ];
        });

        return response()->json($stats);
    }

    /**
     * Get recent admin activities.
     */
    public function recentActivities(Request $request)
    {
        $activities = AdminLog::with('admin')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($activities);
    }

    /**
     * Get system settings.
     */
    public function settings(Request $request)
    {
        $settings = SystemSetting::when($request->group, function ($query, $group) {
            return $query->where('group', $group);
        })->get();

        return response()->json($settings);
    }

    /**
     * Update system settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($request->settings as $setting) {
            SystemSetting::setValue(
                $setting['key'],
                $setting['value'],
                $setting['type'] ?? 'string',
                $setting['group'] ?? 'general',
                $setting['description'] ?? null,
                $setting['is_public'] ?? false
            );
        }

        Cache::forget('system_settings');

        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * Get feedback list with filters.
     */
    public function feedback(Request $request)
    {
        $query = Feedback::with(['user', 'assignedTo'])
            ->when($request->type, function ($query, $type) {
                return $query->ofType($type);
            })
            ->when($request->status, function ($query, $status) {
                return $query->withStatus($status);
            })
            ->when($request->priority, function ($query, $priority) {
                return $query->withPriority($priority);
            })
            ->when($request->assigned_to, function ($query, $assignedTo) {
                return $query->assignedTo($assignedTo);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            });

        $feedback = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json($feedback);
    }

    /**
     * Get system performance metrics.
     */
    public function performance()
    {
        $performance = $this->adminDashboardService->getDetailedPerformanceMetrics();

        return response()->json($performance);
    }

    /**
     * Get AI usage statistics.
     */
    public function aiUsage()
    {
        $aiStats = $this->adminDashboardService->getDetailedAiUsageStats();

        return response()->json($aiStats);
    }

    /**
     * Get subscription statistics.
     */
    public function subscriptionStats()
    {
        $stats = $this->adminDashboardService->getDetailedSubscriptionStats();

        return response()->json($stats);
    }

    /**
     * Get user growth statistics.
     */
    public function userGrowth()
    {
        $growth = $this->adminDashboardService->getUserGrowthStats();

        return response()->json($growth);
    }

    /**
     * Get team growth statistics.
     */
    public function teamGrowth()
    {
        $growth = $this->adminDashboardService->getTeamGrowthStats();

        return response()->json($growth);
    }

    /**
     * Get revenue statistics.
     */
    public function revenueStats()
    {
        $revenue = $this->adminDashboardService->getDetailedRevenueStats();

        return response()->json($revenue);
    }
} 