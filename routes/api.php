<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\AutoReplyController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\ContentIdeaController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\AudienceInsightController;
use App\Http\Controllers\MediaLibraryController;
use App\Http\Controllers\AIModelFeedbackController;
use App\Http\Controllers\ReplyTemplateController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientNoteController;
use App\Http\Controllers\AudiencePersonaController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ContentRecycleController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ImageToVideoController;
use App\Http\Controllers\AIMonthlyPlanController;
use App\Http\Controllers\SmartReplyController;
use App\Http\Controllers\PerformanceAlertController;
use App\Http\Controllers\SEOToolsController;
use App\Http\Controllers\MarketingGoalController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\SentimentController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\AdInsightController;
use App\Http\Controllers\AdInsightAlertController;
use App\Http\Controllers\AdInsightRecommendationController;
use App\Http\Controllers\MessageClassificationController;
use App\Http\Controllers\MessageReminderController;
use App\Http\Controllers\SmartReplyTemplateController;
use App\Http\Controllers\SmartReplyPerformanceController;
use App\Http\Controllers\CustomSentimentWordController;

Route::prefix('auth')->group(function () {
    Route::middleware(['rate.limit:5,1'])->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });
    
    Route::middleware(['auth:sanctum', 'ip.access'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all-devices', [AuthController::class, 'logoutFromAllDevices']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('check', [AuthController::class, 'check']);
    });
});

Route::prefix('roles')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/assign', [RoleController::class, 'assign']);
});

Route::middleware(['auth:sanctum'])->prefix('team')->group(function () {
    Route::post('/create', [TeamController::class, 'createTeam']);
    Route::get('/members', [TeamController::class, 'listMembers'])->middleware('member_of_team');
    Route::post('/add-member', [TeamController::class, 'addMember'])->middleware('member_of_team');
    Route::post('/remove-member', [TeamController::class, 'removeMember'])->middleware('member_of_team');
    Route::post('/change-role', [TeamController::class, 'changeMemberRole'])->middleware('member_of_team');
});

Route::middleware(['auth:sanctum'])->prefix('accounts')->group(function () {
    Route::get('/', [SocialAccountController::class, 'index']);
    Route::post('/connect', [SocialAccountController::class, 'connect']);
    Route::delete('/{id}', [SocialAccountController::class, 'disconnect']);
    Route::put('/{id}/token', [SocialAccountController::class, 'updateToken']);
    Route::put('/{id}/toggle-status', [SocialAccountController::class, 'toggleStatus']);
});

Route::middleware(['auth:sanctum'])->prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::post('/create', [PostController::class, 'store']);
    Route::get('/{id}', [PostController::class, 'show']);
    Route::put('/{id}', [PostController::class, 'update']);
    Route::delete('/{id}', [PostController::class, 'destroy']);
    Route::post('/publish-now/{id}', [PostController::class, 'publishNow']);
});

Route::middleware(['auth:sanctum'])->prefix('inbox')->group(function () {
    Route::get('/', [InboxController::class, 'index']);
    Route::post('/reply', [InboxController::class, 'reply']);
    Route::post('/mark-as-read', [InboxController::class, 'markAsRead']);
});

Route::prefix('auto-replies')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [AutoReplyController::class, 'index']);
    Route::post('/store', [AutoReplyController::class, 'store']);
    Route::delete('/{id}', [AutoReplyController::class, 'destroy']);
});

Route::prefix('insights')->middleware(['auth:sanctum', 'permission:view_insights'])->group(function () {
    Route::get('/posts', [InsightController::class, 'postInsights']);
    Route::get('/accounts', [InsightController::class, 'accountInsights']);
    Route::get('/overview', [InsightController::class, 'dashboardOverview']);
    Route::get('/export', [InsightController::class, 'exportReport']);
});

Route::prefix('content-ideas')->middleware(['auth:sanctum', 'member_of_team'])->group(function () {
    Route::get('/', [ContentIdeaController::class, 'index']);
    Route::post('/generate', [ContentIdeaController::class, 'generate']);
    Route::post('/save', [ContentIdeaController::class, 'store']);
});

Route::prefix('2fa')->middleware(['auth:sanctum'])->group(function () {
    Route::post('enable', [TwoFactorAuthController::class, 'enable']);
    Route::post('verify', [TwoFactorAuthController::class, 'verify']);
    Route::post('verify-recovery', [TwoFactorAuthController::class, 'verifyRecoveryCode']);
    Route::post('disable', [TwoFactorAuthController::class, 'disable']);
    Route::post('send-sms', [TwoFactorAuthController::class, 'sendSmsCode']);
    Route::post('send-whatsapp', [TwoFactorAuthController::class, 'sendWhatsappCode']);
});

Route::prefix('password')->group(function () {
    Route::post('/forgot', [PasswordResetController::class, 'requestReset']);
    Route::post('/reset', [PasswordResetController::class, 'resetPassword']);
});

Route::prefix('backups')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/', [BackupController::class, 'index']);
    Route::post('/create', [BackupController::class, 'create']);
    Route::get('/download/{filename}', [BackupController::class, 'download'])->name('backups.download');
    Route::delete('/{filename}', [BackupController::class, 'destroy']);
});

Route::prefix('audience-insights')->middleware(['auth:sanctum', 'permission:view_insights'])->group(function () {
    Route::get('/{account}', [AudienceInsightController::class, 'getInsights']);
    Route::get('/compare', [AudienceInsightController::class, 'comparePlatforms']);
    Route::get('/recommendations', [AudienceInsightController::class, 'recommendSegments']);
    
    // New routes for alerts
    Route::get('/alerts', [AudienceInsightController::class, 'getAlerts']);
    Route::post('/alerts/{alert}/read', [AudienceInsightController::class, 'markAlertAsRead']);
    Route::post('/alerts/{alert}/resolve', [AudienceInsightController::class, 'markAlertAsResolved']);
    
    // New routes for report generation
    Route::post('/report', [AudienceInsightController::class, 'generateReport']);
    Route::post('/report/{account}', [AudienceInsightController::class, 'generateReport']);
    Route::get('/report/download/{filename}', [AudienceInsightController::class, 'downloadReport'])
        ->name('audience-insights.download-report');

    // Comparison Routes
    Route::post('/{account}/comparison', [AudienceInsightController::class, 'generateComparison']);
    Route::get('/{account}/comparisons', [AudienceInsightController::class, 'getComparisons']);
    Route::get('/comparison/{comparison}', [AudienceInsightController::class, 'getComparison']);
});

// Media Library Routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('media-library')->group(function () {
        Route::get('/', [MediaLibraryController::class, 'index']);
        Route::post('/', [MediaLibraryController::class, 'store']);
        Route::get('/{mediaItem}', [MediaLibraryController::class, 'show']);
        Route::put('/{mediaItem}', [MediaLibraryController::class, 'update']);
        Route::delete('/{mediaItem}', [MediaLibraryController::class, 'destroy']);
        Route::post('/{mediaItem}/star', [MediaLibraryController::class, 'toggleStarred']);
        Route::post('/{mediaItem}/tags', [MediaLibraryController::class, 'addTags']);
        Route::delete('/{mediaItem}/tags', [MediaLibraryController::class, 'removeTags']);
        Route::post('/{mediaItem}/optimize', [MediaLibraryController::class, 'optimize']);
        Route::get('/{mediaItem}/download', [MediaLibraryController::class, 'download']);
        Route::get('/{mediaItem}/thumbnail', [MediaLibraryController::class, 'getThumbnail']);
    });
});

// AI Model Feedback Routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('ai-feedback')->group(function () {
        Route::get('/', [AIModelFeedbackController::class, 'index']);
        Route::post('/', [AIModelFeedbackController::class, 'store']);
        Route::get('/{feedback}', [AIModelFeedbackController::class, 'show']);
        Route::post('/{feedback}/resolve', [AIModelFeedbackController::class, 'resolve']);
        Route::get('/stats', [AIModelFeedbackController::class, 'getFeedbackStats']);
    });
});

Route::prefix('reply-templates')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ReplyTemplateController::class, 'index']);
    Route::post('/', [ReplyTemplateController::class, 'store']);
    Route::put('/{template}', [ReplyTemplateController::class, 'update']);
    Route::delete('/{template}', [ReplyTemplateController::class, 'destroy']);
    Route::post('/{template}/use/{message}', [ReplyTemplateController::class, 'use']);
    Route::post('/{template}/star', [ReplyTemplateController::class, 'toggleStar']);
    Route::post('/suggest/{message}', [ReplyTemplateController::class, 'suggest']);
    Route::post('/improve', [ReplyTemplateController::class, 'improve']);
    Route::get('/stats', [ReplyTemplateController::class, 'stats']);
});

// Client Management Routes
Route::prefix('clients')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ClientController::class, 'index']);
    Route::post('/', [ClientController::class, 'store']);
    Route::get('/{client}', [ClientController::class, 'show']);
    Route::put('/{client}', [ClientController::class, 'update']);
    Route::delete('/{client}', [ClientController::class, 'destroy']);
    Route::post('/{client}/tags', [ClientController::class, 'addTag']);
    Route::delete('/{client}/tags', [ClientController::class, 'removeTag']);
    Route::post('/{client}/messages/{message}/link', [ClientController::class, 'linkMessage']);
    Route::get('/export', [ClientController::class, 'export']);

    // Client Notes Routes
    Route::get('/{client}/notes', [ClientNoteController::class, 'index']);
    Route::post('/{client}/notes', [ClientNoteController::class, 'store']);
    Route::put('/{client}/notes/{note}', [ClientNoteController::class, 'update']);
    Route::delete('/{client}/notes/{note}', [ClientNoteController::class, 'destroy']);
});

// Audience Personas Routes
Route::prefix('audience-personas')->middleware(['auth:sanctum', 'permission:view_audience_insights'])->group(function () {
    Route::get('/', [AudiencePersonaController::class, 'index']);
    Route::post('/', [AudiencePersonaController::class, 'store'])->middleware('permission:manage_audience_insights');
    Route::get('/{persona}', [AudiencePersonaController::class, 'show']);
    Route::put('/{persona}', [AudiencePersonaController::class, 'update'])->middleware('permission:manage_audience_insights');
    Route::delete('/{persona}', [AudiencePersonaController::class, 'destroy'])->middleware('permission:manage_audience_insights');
    Route::post('/generate', [AudiencePersonaController::class, 'generate'])->middleware('permission:manage_audience_insights');
    Route::get('/{persona}/recommendations', [AudiencePersonaController::class, 'recommendations']);
});

// Coupon routes
Route::prefix('coupons')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [CouponController::class, 'index']);
    Route::post('/', [CouponController::class, 'store']);
    Route::get('/{coupon}', [CouponController::class, 'show']);
    Route::put('/{coupon}', [CouponController::class, 'update']);
    Route::delete('/{coupon}', [CouponController::class, 'destroy']);
    Route::post('/{coupon}/redeem', [CouponController::class, 'redeem']);
    Route::post('/bulk', [CouponController::class, 'generateBulk']);
    Route::post('/{coupon}/assign', [CouponController::class, 'assignToClient']);
    Route::get('/stats', [CouponController::class, 'stats']);
});

// Offers Routes
Route::prefix('offers')->middleware(['auth:sanctum', 'permission:view_offers'])->group(function () {
    Route::get('/', [OfferController::class, 'index']);
    Route::get('/stats', [OfferController::class, 'stats']);
    Route::get('/{offer}', [OfferController::class, 'show']);
    
    Route::middleware(['permission:manage_offers'])->group(function () {
        Route::post('/', [OfferController::class, 'store']);
        Route::put('/{offer}', [OfferController::class, 'update']);
        Route::delete('/{offer}', [OfferController::class, 'destroy']);
        Route::post('/{offer}/generate-coupons', [OfferController::class, 'generateCoupons']);
        Route::post('/{offer}/assign-to-clients', [OfferController::class, 'assignToClients']);
        Route::post('/{offer}/assign-to-personas', [OfferController::class, 'assignToPersonas']);
        Route::post('/{offer}/assign-to-segments', [OfferController::class, 'assignToSegments']);
        Route::post('/auto-generate', [OfferController::class, 'autoGenerate']);
    });
});

// Content Recycling Routes
Route::prefix('content-recycles')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ContentRecycleController::class, 'index']);
    Route::get('/suggestions', [ContentRecycleController::class, 'suggestions']);
    Route::get('/stats', [ContentRecycleController::class, 'stats']);
    Route::post('/', [ContentRecycleController::class, 'store']);
    Route::get('/{contentRecycle}', [ContentRecycleController::class, 'show']);
    Route::put('/{contentRecycle}', [ContentRecycleController::class, 'update']);
    Route::delete('/{contentRecycle}', [ContentRecycleController::class, 'destroy']);
    Route::post('/{contentRecycle}/schedule', [ContentRecycleController::class, 'schedule']);
    Route::get('/{contentRecycle}/compare', [ContentRecycleController::class, 'comparePerformance']);
});

// Admin Dashboard Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
    // Dashboard Overview
    Route::get('dashboard/overview', [AdminDashboardController::class, 'overview']);
    Route::get('dashboard/activities', [AdminDashboardController::class, 'recentActivities']);
    
    // System Settings
    Route::get('settings', [AdminDashboardController::class, 'settings']);
    Route::put('settings', [AdminDashboardController::class, 'updateSettings']);
    
    // Feedback Management
    Route::get('feedback', [AdminDashboardController::class, 'feedback']);
    
    // Statistics
    Route::get('stats/performance', [AdminDashboardController::class, 'performance']);
    Route::get('stats/ai-usage', [AdminDashboardController::class, 'aiUsage']);
    Route::get('stats/subscriptions', [AdminDashboardController::class, 'subscriptionStats']);
    Route::get('stats/user-growth', [AdminDashboardController::class, 'userGrowth']);
    Route::get('stats/team-growth', [AdminDashboardController::class, 'teamGrowth']);
    Route::get('stats/revenue', [AdminDashboardController::class, 'revenueStats']);
});

// Security Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('security')->group(function () {
    Route::get('stats', [SecurityController::class, 'stats']);
    Route::get('logs', [SecurityController::class, 'logs']);
    Route::get('ip-whitelist', [SecurityController::class, 'ipWhitelist']);
    Route::post('ip-whitelist', [SecurityController::class, 'addToWhitelist']);
    Route::delete('ip-whitelist/{ipAddress}', [SecurityController::class, 'removeFromWhitelist']);
    Route::get('suspicious-activity', [SecurityController::class, 'suspiciousActivity']);
    Route::get('device-stats', [SecurityController::class, 'deviceStats']);
});

// API Keys Routes
Route::prefix('api-keys')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/', [ApiKeyController::class, 'create']);
    Route::get('/', [ApiKeyController::class, 'index']);
    Route::post('/{apiKey}/revoke', [ApiKeyController::class, 'revoke']);
    Route::post('/{apiKey}/extend', [ApiKeyController::class, 'extend']);
    Route::post('/{apiKey}/never-expire', [ApiKeyController::class, 'setNeverExpire']);
});

// Image to Video Conversion Routes
Route::prefix('media')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/convert-to-video', [ImageToVideoController::class, 'convertToVideo']);
    Route::get('/videos', [ImageToVideoController::class, 'index']);
});

// AI Monthly Content Planner Routes
Route::prefix('ai-monthly-plan')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/generate', [AIMonthlyPlanController::class, 'generate']);
    Route::get('/{id}', [AIMonthlyPlanController::class, 'show']);
    Route::put('/{id}', [AIMonthlyPlanController::class, 'update']);
    Route::post('/{id}/schedule', [AIMonthlyPlanController::class, 'scheduleFromPlan']);
});

// Smart Reply Generator Routes
Route::prefix('messages')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/analyze', [SmartReplyController::class, 'analyzeMessage']);
    Route::post('/generate-reply', [SmartReplyController::class, 'generateReply']);
    Route::post('/send-reply', [SmartReplyController::class, 'sendReply']);
});

// Performance Alerts Routes
Route::prefix('performance-alerts')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/low-performance', [PerformanceAlertController::class, 'getLowPerformancePosts']);
    Route::get('/improve/{postId}', [PerformanceAlertController::class, 'getImprovementSuggestions']);
    Route::post('/reschedule/{postId}', [PerformanceAlertController::class, 'reschedulePost']);
    Route::get('/settings', [PerformanceAlertController::class, 'getAlertSettings']);
    Route::put('/settings', [PerformanceAlertController::class, 'updateAlertSettings']);
});

// SEO Tools Routes
Route::prefix('seo')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/analyze-post', [SEOToolsController::class, 'analyzePost']);
    Route::post('/generate-meta', [SEOToolsController::class, 'generateMeta']);
    Route::get('/score/{postId}', [SEOToolsController::class, 'getSEOScore']);
    Route::post('/keyword-suggestions', [SEOToolsController::class, 'getKeywordSuggestions']);
    Route::post('/optimize-content', [SEOToolsController::class, 'optimizeContent']);
});

// Marketing Goals Routes
Route::prefix('goals')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [MarketingGoalController::class, 'index']);
    Route::post('/', [MarketingGoalController::class, 'store']);
    Route::get('/progress', [MarketingGoalController::class, 'getProgress']);
    Route::put('/{id}', [MarketingGoalController::class, 'update']);
    Route::delete('/{id}', [MarketingGoalController::class, 'destroy']);
    Route::get('/{id}/recommendations', [MarketingGoalController::class, 'getRecommendations']);
});

// Calendar Events Routes
Route::prefix('calendar')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/events', [CalendarEventController::class, 'index']);
    Route::post('/events', [CalendarEventController::class, 'store']);
    Route::get('/suggestions', [CalendarEventController::class, 'getSuggestions']);
    Route::get('/notifications', [CalendarEventController::class, 'getUpcomingNotifications']);
    Route::put('/events/{id}', [CalendarEventController::class, 'update']);
    Route::delete('/events/{id}', [CalendarEventController::class, 'destroy']);
    Route::get('/events/{id}/content-ideas', [CalendarEventController::class, 'getEventContentIdeas']);
});

// Report routes
Route::prefix('reports')->group(function () {
    Route::post('generate', [ReportController::class, 'generate']);
    Route::get('scheduled', [ReportController::class, 'getScheduledReports']);
    Route::get('{id}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::post('schedule', [ReportController::class, 'schedule']);
});

// Approval Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('posts/{post}/request-approval', [ApprovalController::class, 'requestApproval']);
    Route::post('approvals/{approvalRequest}/approve', [ApprovalController::class, 'approve']);
    Route::post('approvals/{approvalRequest}/reject', [ApprovalController::class, 'reject']);
    Route::get('approvals/pending', [ApprovalController::class, 'pendingApprovals']);
});

// Sentiment Analysis Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('sentiment/analyze', [SentimentController::class, 'analyze']);
    Route::get('sentiment/stats', [SentimentController::class, 'getStats']);
});

// Thread Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('threads/generate', [ThreadController::class, 'generate']);
    Route::post('threads/{thread}/schedule', [ThreadController::class, 'schedule']);
    Route::post('threads/{thread}/publish', [ThreadController::class, 'publish']);
});

// Smart Reply Routes
Route::prefix('smart-replies')->group(function () {
    Route::get('templates', [SmartReplyController::class, 'getTemplates']);
    Route::put('templates', [SmartReplyController::class, 'updateTemplates']);
    Route::post('messages/{message}/generate', [SmartReplyController::class, 'generateReply']);
});

// Ad Insights Routes
Route::prefix('ad-insights')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [AdInsightController::class, 'index']);
    Route::get('/{adInsight}', [AdInsightController::class, 'show']);
    Route::post('/fetch', [AdInsightController::class, 'fetchInsights']);
});

// Ad Insights Alert Routes
Route::prefix('ad-insights/alerts')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [AdInsightAlertController::class, 'index']);
    Route::get('/stats', [AdInsightAlertController::class, 'stats']);
    Route::get('/{alert}', [AdInsightAlertController::class, 'show']);
    Route::post('/{alert}/resolve', [AdInsightAlertController::class, 'resolve']);
});

// Ad Insight Recommendations
Route::prefix('ad-insight-recommendations')->group(function () {
    Route::get('/', [AdInsightRecommendationController::class, 'index']);
    Route::get('/stats', [AdInsightRecommendationController::class, 'stats']);
    Route::get('/{recommendation}', [AdInsightRecommendationController::class, 'show']);
    Route::post('/ads/{ad}/generate', [AdInsightRecommendationController::class, 'generateForAd']);
    Route::post('/{recommendation}/implement', [AdInsightRecommendationController::class, 'implement']);
    Route::put('/{recommendation}/results', [AdInsightRecommendationController::class, 'updateResults']);
});

// Message Classification Routes
Route::prefix('message-classification')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [MessageClassificationController::class, 'index']);
    Route::get('/stats', [MessageClassificationController::class, 'stats']);
    Route::post('/messages/{message}/label', [MessageClassificationController::class, 'updateLabel']);
    Route::post('/messages/{message}/notes', [MessageClassificationController::class, 'addNote']);
});

// Message Reminder Routes
Route::prefix('message-reminders')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [MessageReminderController::class, 'index']);
    Route::post('/messages/{message}/reminders', [MessageReminderController::class, 'store']);
    Route::delete('/messages/{message}/reminders', [MessageReminderController::class, 'destroy']);
});

// Smart Reply Template Routes
Route::prefix('smart-reply-templates')->group(function () {
    Route::get('/', [SmartReplyTemplateController::class, 'index']);
    Route::get('/categories', [SmartReplyTemplateController::class, 'categories']);
    Route::post('/', [SmartReplyTemplateController::class, 'store']);
    Route::get('/{template}', [SmartReplyTemplateController::class, 'show']);
    Route::put('/{template}', [SmartReplyTemplateController::class, 'update']);
    Route::delete('/{template}', [SmartReplyTemplateController::class, 'destroy']);
    Route::put('/{template}/success-rate', [SmartReplyTemplateController::class, 'updateSuccessRate']);
});

// Smart Reply Performance Routes
Route::prefix('smart-reply-performance')->group(function () {
    Route::get('/overall', [SmartReplyPerformanceController::class, 'getOverallPerformance']);
    Route::get('/templates/{template}', [SmartReplyPerformanceController::class, 'getTemplatePerformance']);
    Route::post('/templates/{template}/update', [SmartReplyPerformanceController::class, 'updateTemplatePerformance']);
    Route::get('/templates/{template}/suggestions', [SmartReplyPerformanceController::class, 'getImprovementSuggestions']);
});

// Custom Sentiment Words Routes
Route::prefix('custom-sentiment-words')->group(function () {
    Route::get('/', [CustomSentimentWordController::class, 'index']);
    Route::get('/categories', [CustomSentimentWordController::class, 'getCategories']);
    Route::post('/', [CustomSentimentWordController::class, 'store']);
    Route::post('/bulk-import', [CustomSentimentWordController::class, 'bulkImport']);
    Route::get('/{word}', [CustomSentimentWordController::class, 'show']);
    Route::put('/{word}', [CustomSentimentWordController::class, 'update']);
    Route::delete('/{word}', [CustomSentimentWordController::class, 'destroy']);
    Route::post('/{word}/toggle-status', [CustomSentimentWordController::class, 'toggleStatus']);
}); 