<?php

use App\Core\Http\Controllers\AuthController;
use App\Core\Http\Controllers\CommunityAnnouncementController;
use App\Core\Http\Controllers\CommunityContentController;
use App\Core\Http\Controllers\CommunityDiscussionController;
use App\Core\Http\Controllers\CommunityLabResultController;
use App\Core\Http\Controllers\CommunityMemberController;
use App\Core\Http\Controllers\CommunityMessageController;
use App\Core\Http\Controllers\CommunityNotificationController;
use App\Core\Http\Controllers\CommunityUserActionController;
use App\Core\Http\Controllers\CommunityVendorController;
use App\Core\Http\Controllers\EmojiController;
use App\Core\Http\Controllers\PluginController;
use App\Core\Http\Controllers\PushSubscriptionController;
use App\Core\Http\Controllers\VendorAccessRequestController;
use App\Core\Http\Controllers\UserSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Unversioned — server-to-server callbacks ──────────────────────────────────
// These URLs must never change. External systems (licensing servers, payment
// processors) call them and cannot be transparently redirected on a version bump.

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/license/callback', [\App\Core\Http\Controllers\Admin\LicenseController::class, 'activationCallback']);
});

// ── v1 API ────────────────────────────────────────────────────────────────────
// All client-facing routes live under /api/v1/.
// When breaking changes are required, create a /api/v2/ prefix group here and
// run both groups in parallel during the migration window.

Route::prefix('v1')->group(function () {

    // Public plugin info (for frontend navigation)
    Route::get('/plugins/enabled', [PluginController::class, 'enabled']);

    // Public license routes (for installation/setup)
    Route::prefix('license')->controller(\App\Core\Http\Controllers\Admin\LicenseController::class)->group(function () {
        Route::get('/status', 'status');
        Route::post('/activate', 'activate');
    });

    // Public authentication routes (rate limited to prevent brute force)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Password Reset Routes
        Route::post('/forgot-password', [\App\Core\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink']);
        Route::post('/validate-reset-token', [\App\Core\Http\Controllers\Auth\PasswordResetController::class, 'validateToken']);
        Route::post('/reset-password', [\App\Core\Http\Controllers\Auth\PasswordResetController::class, 'resetPassword']);

        // Two-Factor Authentication (public routes for login verification)
        Route::post('/2fa/verify', [\App\Core\Http\Controllers\Auth\TwoFactorAuthController::class, 'verify']);

        // OAuth Routes
        Route::get('/oauth/providers', [\App\Core\Http\Controllers\Auth\OAuthController::class, 'providers']);
        Route::get('/oauth/{provider}/redirect', [\App\Core\Http\Controllers\Auth\OAuthController::class, 'redirect']);
        Route::get('/oauth/{provider}/callback', [\App\Core\Http\Controllers\Auth\OAuthController::class, 'callback']);
        Route::post('/oauth/{provider}/complete', [\App\Core\Http\Controllers\Auth\OAuthController::class, 'complete']);
    });

    // Frontend error logging (rate limited to prevent log flooding)
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/log-frontend-error', [\App\Core\Http\Controllers\FrontendErrorController::class, 'log']);
        Route::post('/log-api-error', [\App\Core\Http\Controllers\FrontendErrorController::class, 'logApiError']);
        Route::post('/log-vue-error', [\App\Core\Http\Controllers\FrontendErrorController::class, 'logVueError']);
    });

    // Public settings
    Route::get('/settings/public', [\App\Core\Http\Controllers\PublicSettingsController::class, 'index']);

    // Public push VAPID key (needed before subscribing)
    Route::get('/push/vapid-key', [PushSubscriptionController::class, 'vapidPublicKey']);

    // Public membership plans
    Route::get('/membership/plans', [\App\Core\Http\Controllers\MembershipController::class, 'plans']);

    // Data deletion request (public)
    Route::post('/data-deletion-request', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'reason' => 'nullable|string|max:2000',
        ]);
        \App\Core\Models\DataDeletionRequest::create($validated);
        return response()->json(['message' => 'Request received. We will process it within 30 days.']);
    });

    // Public community routes (guests and members — tier gating is internal)
    Route::prefix('community')->group(function () {
        Route::get('/discussion-categories', [CommunityDiscussionController::class, 'categories']);
        Route::get('/discussions', [CommunityDiscussionController::class, 'index']);
        Route::get('/discussions/{discussion}', [CommunityDiscussionController::class, 'show'])->middleware(\App\Core\Middleware\CheckDailyLimit::class);
        Route::get('/announcements', [CommunityAnnouncementController::class, 'index']);
        Route::get('/announcements/{announcement}', [CommunityAnnouncementController::class, 'show']);
        Route::get('/research-library', [CommunityContentController::class, 'researchIndex']);
        Route::get('/research-library/{content}', [CommunityContentController::class, 'researchShow']);
        Route::get('/guides', [CommunityContentController::class, 'guideIndex']);
        Route::get('/guides/{content}', [CommunityContentController::class, 'guideShow']);
        Route::get('/faqs', [CommunityContentController::class, 'faqIndex']);
        Route::get('/search', [\App\Core\Http\Controllers\SearchController::class, 'search']);
        Route::get('/lab-results', [CommunityLabResultController::class, 'index'])->middleware('tier:paid');
        Route::get('/lab-results/{result}', [CommunityLabResultController::class, 'show'])->middleware('tier:paid');
        Route::get('/vendors', [CommunityVendorController::class, 'index'])->middleware('tier:paid');
        Route::get('/vendors/{vendor}', [CommunityVendorController::class, 'show'])->middleware('tier:paid');
    });

    // Authenticated community content
    Route::middleware('auth:sanctum')->prefix('community')->group(function () {
        Route::get('/members', [CommunityMemberController::class, 'index'])->middleware('tier:paid');
        Route::get('/members/{member}', [CommunityMemberController::class, 'show'])->middleware('tier:paid');
        Route::get('/messages', [CommunityMessageController::class, 'index'])->middleware('tier:paid');
        Route::get('/messages/{thread}', [CommunityMessageController::class, 'show'])->middleware('tier:paid');
        Route::get('/notifications', [CommunityNotificationController::class, 'index']);
        Route::get('/notifications/{notification}', [CommunityNotificationController::class, 'show']);
    });

    // WebSocket routes (public - for guest tracking)
    Route::prefix('ws')->controller(\App\Core\Http\Controllers\WebSocketController::class)->withoutMiddleware('auth:sanctum')->group(function () {
        Route::get('/online-count', 'onlineCount');
        Route::post('/heartbeat', 'heartbeat');
    });

    // Protected authentication routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/upload/image', function (\Illuminate\Http\Request $request) {
            $request->validate(['image' => 'required|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,pdf,x-pdf|max:25600']);
            $path = $request->file('image')->store('uploads/images', 'public');
            return response()->json(['url' => \Illuminate\Support\Facades\Storage::url($path), 'path' => $path]);
        });
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::patch('/user', [UserSettingsController::class, 'update']);
        Route::post('/user/change-password', [AuthController::class, 'changePassword']);
        Route::post('/user/username', [AuthController::class, 'updateUsername']);
        Route::patch('/user/profile', [UserSettingsController::class, 'updateProfile']);
        Route::post('/user/avatar', [UserSettingsController::class, 'avatar']);
        Route::get('/user/notification-settings', [UserSettingsController::class, 'notificationSettings']);
        Route::patch('/user/notification-settings', [UserSettingsController::class, 'updateNotificationSettings']);
        Route::get('/user/preferences', [UserSettingsController::class, 'preferences']);
        Route::patch('/user/preferences', [UserSettingsController::class, 'updatePreferences']);
        Route::get('/user/privacy', [UserSettingsController::class, 'privacy']);
        Route::patch('/user/privacy', [UserSettingsController::class, 'updatePrivacy']);
        Route::get('/user/sessions', [UserSettingsController::class, 'sessions']);
        Route::delete('/user/sessions/{session}', [UserSettingsController::class, 'deleteSession']);
        Route::get('/user/api-tokens', [UserSettingsController::class, 'apiTokens']);
        Route::post('/user/api-tokens', [UserSettingsController::class, 'createApiToken']);
        Route::delete('/user/api-tokens/{token}', [UserSettingsController::class, 'deleteApiToken']);
        Route::get('/user/blocked-users', [UserSettingsController::class, 'blockedUsers']);
        Route::post('/user/blocked-users', [UserSettingsController::class, 'blockUser']);
        Route::delete('/user/blocked-users/{user}', [UserSettingsController::class, 'unblockUser']);

        Route::post('/vendor-access/request', [VendorAccessRequestController::class, 'store']);

        Route::prefix('community')->group(function () {
            Route::get('/user-actions', [CommunityUserActionController::class, 'index']);
            Route::post('/user-actions/toggle', [CommunityUserActionController::class, 'toggle']);
            Route::post('/discussions', [CommunityDiscussionController::class, 'store']);
            Route::patch('/discussions/{discussion}', [CommunityDiscussionController::class, 'update']);
            Route::delete('/discussions/{discussion}', [CommunityDiscussionController::class, 'destroy']);
            Route::post('/discussions/{discussion}/replies', [CommunityDiscussionController::class, 'reply']);
            Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe']);
            Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
            Route::post('/discussions/{discussion}/vote', [CommunityDiscussionController::class, 'voteOnDiscussion']);
            Route::post('/discussions/{discussion}/report', [CommunityDiscussionController::class, 'reportDiscussion']);
            Route::post('/discussion-replies/{reply}/vote', [CommunityDiscussionController::class, 'voteOnReply']);
            Route::post('/discussion-replies/{reply}/report', [CommunityDiscussionController::class, 'reportReply']);
            Route::delete('/discussion-replies/{reply}', [CommunityDiscussionController::class, 'destroyReply']);

            // Paid-only routes
            Route::middleware('tier:paid')->group(function () {
                Route::post('/lab-results', [CommunityLabResultController::class, 'store']);
                Route::get('/vendor-profile', [CommunityVendorController::class, 'myVendorProfile']);
                Route::post('/vendor-profile/image', [CommunityVendorController::class, 'uploadVendorImage']);
                Route::post('/vendor-profile', [CommunityVendorController::class, 'storeVendorProfile']);
                Route::patch('/vendor-profile', [CommunityVendorController::class, 'updateVendorProfile']);
                Route::post('/vendor-profile/products', [CommunityVendorController::class, 'storeVendorProduct']);
                Route::post('/vendor-profile/products/{product}', [CommunityVendorController::class, 'updateVendorProduct']);
                Route::patch('/vendor-profile/products/{product}', [CommunityVendorController::class, 'updateVendorProduct']);
                Route::delete('/vendor-profile/products/{product}', [CommunityVendorController::class, 'destroyVendorProduct']);
                Route::post('/vendor-profile/documents', [CommunityVendorController::class, 'storeVendorDocument']);
                Route::delete('/vendor-profile/documents/{document}', [CommunityVendorController::class, 'destroyVendorDocument']);
                Route::post('/vendors/{vendor}/claim', [CommunityVendorController::class, 'claimVendor']);
                Route::post('/vendors/{vendor}/reviews', [CommunityVendorController::class, 'storeReview']);
                Route::post('/vendor-reviews/{review}/helpful', [CommunityVendorController::class, 'markReviewHelpful']);
                Route::post('/vendor-reviews/{review}/respond', [CommunityVendorController::class, 'respondToReview']);
                Route::post('/messages', [CommunityMessageController::class, 'storeThread']);
                Route::post('/messages/{thread}/messages', [CommunityMessageController::class, 'store']);
            });

            Route::post('/notifications/{notification}/read', [CommunityNotificationController::class, 'markAsRead']);
            Route::post('/notifications/read-all', [CommunityNotificationController::class, 'markAllAsRead']);
            Route::delete('/notifications/read/clear', [CommunityNotificationController::class, 'deleteRead']);
            Route::delete('/notifications/{notification}', [CommunityNotificationController::class, 'delete']);
        });

        // Two-Factor Authentication (authenticated routes)
        Route::prefix('2fa')->controller(\App\Core\Http\Controllers\Auth\TwoFactorAuthController::class)->group(function () {
            Route::get('/status', 'status');
            Route::post('/setup', 'setup');
            Route::post('/confirm', 'confirm');
            Route::post('/disable', 'disable');
            Route::post('/recovery-codes', 'recoveryCodes');
            Route::post('/regenerate-recovery-codes', 'regenerateRecoveryCodes');
        });

        // OAuth Account Linking (authenticated routes)
        Route::prefix('oauth')->controller(\App\Core\Http\Controllers\Auth\OAuthController::class)->group(function () {
            Route::get('/linked', 'linked');
            Route::get('/{provider}/link', 'link');
            Route::delete('/{provider}/unlink', 'unlink');
        });

        // WebSocket Routes (authenticated)
        Route::prefix('ws')->controller(\App\Core\Http\Controllers\WebSocketController::class)->group(function () {
            Route::post('/auth', 'authorizeChannel');
            Route::post('/poll', 'poll');
            Route::get('/presence/{channel}', 'presenceMembers');
        });

        // Admin Panel Routes (require admin role + valid license)
        Route::prefix('admin')->name('admin.')->middleware(['role:admin|moderator', 'verify.license'])->group(function () {

            // Dashboard Statistics
            Route::get('/stats', [\App\Core\Http\Controllers\Admin\DashboardStatsController::class, 'index']);

            // License Management
            Route::prefix('license')->controller(\App\Core\Http\Controllers\Admin\LicenseController::class)->group(function () {
                Route::get('/status', 'status');
                Route::post('/activate', 'activate');
                Route::post('/generate', 'generate');
                Route::delete('/deactivate', 'deactivate');
                Route::get('/keys', 'keys');
                Route::put('/keys/{id}', 'updateKey');
                Route::post('/keys/{id}/revoke', 'revokeKey');
            });

            // Plugin Management
            Route::prefix('plugins')->controller(PluginController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/upload', 'upload');
                Route::post('/create', 'create');
                Route::post('/{slug}/install', 'install');
                Route::delete('/{slug}', 'uninstall');
                Route::put('/{slug}/enable', 'enable');
                Route::put('/{slug}/disable', 'disable');
                Route::put('/{slug}/reactivate', 'reactivate');
                Route::delete('/{slug}/staging', 'removeStaging');
                Route::post('/{slug}/install-theme', 'installTheme');
                Route::put('/{slug}/activate-theme', 'activateTheme');
                Route::get('/themes', 'index')->defaults('type', 'theme');
            });

            // User Management
            Route::prefix('users')->controller(\App\Core\Http\Controllers\Admin\UserManagementController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/statistics', 'statistics');
                Route::post('/', 'store');
                Route::get('/{user}', 'show');
                Route::patch('/{user}', 'update');
                Route::delete('/{user}', 'destroy');
                Route::post('/{user}/ban', 'ban');
                Route::post('/{user}/unban', 'unban');
            Route::patch('/{user}/vendor-access', 'updateVendorAccess');
            Route::post('/{user}/vendor-profile', 'grantVendorProfile');
        });

        // Data Deletion Requests
        Route::apiResource('data-deletion-requests', \App\Core\Http\Controllers\Admin\DataDeletionController::class)->only(['index', 'show', 'update']);

        // Roles & Permissions
            Route::prefix('roles')->controller(\App\Core\Http\Controllers\Admin\RolePermissionController::class)->group(function () {
                Route::get('/', 'indexRoles');
                Route::post('/', 'storeRole');
                Route::patch('/{id}', 'updateRole');
                Route::delete('/{id}', 'destroyRole');
            });
            Route::get('/permissions', [\App\Core\Http\Controllers\Admin\RolePermissionController::class, 'indexPermissions']);
            Route::post('/users/{id}/roles', [\App\Core\Http\Controllers\Admin\RolePermissionController::class, 'assignRoleToUser']);
            Route::delete('/users/{id}/roles', [\App\Core\Http\Controllers\Admin\RolePermissionController::class, 'removeRoleFromUser']);

            // Settings
            Route::prefix('settings')->controller(\App\Core\Http\Controllers\Admin\SettingsController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/all', 'allWithDefaults');
                Route::get('/plugin-schema', 'pluginSettingsSchema');
                Route::post('/', 'store');
                Route::patch('/', 'update');
                Route::get('/security', 'securityIndex');
                Route::post('/oauth/{provider}', 'saveOAuthProvider');
                Route::get('/{key}', 'show');
                Route::delete('/{key}', 'destroy');
            });

            // Webhooks Management
            Route::prefix('webhooks')->controller(\App\Core\Http\Controllers\Admin\WebhookController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/events', 'events');
                Route::post('/', 'store');
                Route::get('/{id}', 'show');
                Route::patch('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
                Route::post('/{id}/toggle', 'toggle');
                Route::post('/{id}/test', 'test');
                Route::get('/{id}/deliveries', 'deliveries');
                Route::post('/{id}/deliveries/{deliveryId}/retry', 'retryDelivery');
                Route::post('/{id}/regenerate-secret', 'regenerateSecret');
            });

            // Email Settings & Templates
            Route::prefix('email')->controller(\App\Core\Http\Controllers\Admin\EmailSettingsController::class)->group(function () {
                Route::get('/settings', 'getSettings');
                Route::post('/settings', 'updateSettings');
                Route::post('/settings/test', 'testSettings');
                Route::post('/send', 'sendManualEmail');
                Route::get('/templates', 'getTemplates');
                Route::post('/templates', 'createTemplate');
                Route::post('/templates/seed-defaults', 'seedDefaultTemplates');
                Route::get('/templates/{id}', 'getTemplate');
                Route::patch('/templates/{id}', 'updateTemplate');
                Route::delete('/templates/{id}', 'deleteTemplate');
                Route::post('/templates/{id}/preview', 'previewTemplate');
                Route::post('/templates/{id}/test', 'sendTestTemplate');
            });

            Route::prefix('community/discussions')->controller(\App\Core\Http\Controllers\Admin\CommunityDiscussionModerationController::class)->group(function () {
                Route::get('/', 'index');
                Route::patch('/{discussion}', 'update');
                Route::delete('/{discussion}', 'destroy');
            });

            Route::apiResource('community/categories', \App\Core\Http\Controllers\Admin\CommunityCategoryAdminController::class)
                ->parameters(['categories' => 'category']);

            Route::prefix('community/lab-results')->controller(\App\Core\Http\Controllers\Admin\CommunityLabResultAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::patch('/{result}', 'update');
                Route::delete('/{result}', 'destroy');
            });

            Route::prefix('community/vendor-reviews')->controller(\App\Core\Http\Controllers\Admin\CommunityVendorReviewAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::patch('/{review}', 'update');
                Route::delete('/{review}', 'destroy');
            });

            Route::prefix('community/vendors')->controller(\App\Core\Http\Controllers\Admin\CommunityVendorAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('/{vendor}', 'update');
                Route::delete('/{vendor}', 'destroy');
            });

            Route::prefix('community/vendor-products')->controller(\App\Core\Http\Controllers\Admin\CommunityVendorProductAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('/{product}', 'update');
                Route::delete('/{product}', 'destroy');
            });

            Route::prefix('community/vendor-claims')->controller(\App\Core\Http\Controllers\Admin\CommunityVendorClaimAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::patch('/{claim}', 'update');
            });

            Route::prefix('community/announcements')->controller(\App\Core\Http\Controllers\Admin\CommunityAnnouncementAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('/{announcement}', 'update');
                Route::delete('/{announcement}', 'destroy');
            });

            Route::prefix('community/notifications')->controller(\App\Core\Http\Controllers\Admin\CommunityNotificationAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('/{notification}', 'update');
                Route::delete('/{notification}', 'destroy');
            });

            Route::prefix('community/access-codes')->controller(\App\Core\Http\Controllers\Admin\CommunityAccessCodeAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::delete('/{accessCode}', 'destroy');
            });

            Route::get('/vendor-access/requests', [VendorAccessRequestController::class, 'index']);
            Route::patch('/vendor-access/requests/{vendorAccessRequest}', [VendorAccessRequestController::class, 'update']);

            Route::prefix('community/content')->controller(\App\Core\Http\Controllers\Admin\CommunityContentAdminController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('/{content}', 'update');
                Route::delete('/{content}', 'destroy');
            });

            // Locations and Memberships are now provided by plugins
            // Route::apiResource('locations', \App\Core\Http\Controllers\Admin\LocationController::class);
            // Route::apiResource('memberships', \App\Core\Http\Controllers\Admin\MembershipController::class);

            // Configurable Type Tables (Core - for plugin extensibility)
            Route::apiResource('item-rarities', \App\Core\Http\Controllers\Admin\ItemRarityController::class);
            Route::apiResource('property-types', \App\Core\Http\Controllers\Admin\PropertyTypeController::class);
            Route::apiResource('announcement-types', \App\Core\Http\Controllers\Admin\AnnouncementTypeController::class);
            Route::apiResource('crime-difficulties', \App\Core\Http\Controllers\Admin\CrimeDifficultyController::class);
            Route::apiResource('casino-game-types', \App\Core\Http\Controllers\Admin\CasinoGameTypeController::class);
            Route::apiResource('company-industries', \App\Core\Http\Controllers\Admin\CompanyIndustryController::class);
            Route::apiResource('stock-sectors', \App\Core\Http\Controllers\Admin\StockSectorController::class);
            Route::apiResource('course-skills', \App\Core\Http\Controllers\Admin\CourseSkillController::class);
            Route::apiResource('course-difficulties', \App\Core\Http\Controllers\Admin\CourseDifficultyController::class);
            Route::apiResource('achievement-stats', \App\Core\Http\Controllers\Admin\AchievementStatController::class);
            Route::apiResource('mission-frequencies', \App\Core\Http\Controllers\Admin\MissionFrequencyController::class);
            Route::apiResource('mission-objective-types', \App\Core\Http\Controllers\Admin\MissionObjectiveTypeController::class);
            Route::apiResource('bounty-statuses', \App\Core\Http\Controllers\Admin\BountyStatusController::class);
            Route::apiResource('lottery-statuses', \App\Core\Http\Controllers\Admin\LotteryStatusController::class);
            Route::apiResource('item-effect-types', \App\Core\Http\Controllers\Admin\ItemEffectTypeController::class);
            Route::apiResource('item-modifier-types', \App\Core\Http\Controllers\Admin\ItemModifierTypeController::class);

            // System Administration
            Route::get('error-logs', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'index']);
            Route::get('error-logs/statistics', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'statistics']);
            Route::get('error-logs/laravel-log', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'laravelLog']);
            Route::post('error-logs/laravel-log/sync', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'syncLaravelLog']);
            Route::delete('error-logs/laravel-log/clear', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'clearLaravelLog']);
            Route::delete('error-logs/clear', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'clearAll']);
            Route::get('error-logs/{id}', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'show']);
            Route::patch('error-logs/{id}/resolve', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'resolve']);
            Route::patch('error-logs/{id}/unresolve', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'unresolve']);
            Route::delete('error-logs/{id}', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'destroy']);
            Route::post('error-logs/bulk-resolve', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'bulkResolve']);
            Route::post('error-logs/bulk-delete', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'bulkDelete']);
            Route::delete('error-logs/resolved/all', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'deleteResolved']);
            Route::delete('error-logs/old', [\App\Core\Http\Controllers\Admin\ErrorLogController::class, 'deleteOld']);

            // Admin Notifications
            Route::prefix('notifications')->controller(\App\Core\Http\Controllers\Admin\AdminNotificationController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/recent', 'recent');
                Route::get('/unread-count', 'unreadCount');
                Route::post('/{id}/read', 'markAsRead');
                Route::post('/read-all', 'markAllAsRead');
                Route::delete('/{id}', 'destroy');
                Route::delete('/clear-read', 'clearRead');
                Route::post('/test', 'sendTest');
                Route::post('/broadcast', 'broadcast');
            });

            Route::apiResource('ip-bans', \App\Core\Http\Controllers\Admin\IpBanController::class);

            // User timers are now provided by plugins
            // Route::get('user-timers', [\App\Core\Http\Controllers\Admin\UserTimerController::class, 'index']);

            // User Tools (Admin)
            Route::prefix('user-tools')->controller(\App\Core\Http\Controllers\Admin\UserToolsController::class)->group(function () {
                Route::get('/search', 'search');
                Route::get('/{id}', 'show');
                Route::get('/{id}/inventory', 'inventory');
                Route::get('/{id}/timers', 'timers');
                Route::delete('/{id}/timers/{timerType}', 'clearTimer');
                Route::get('/{id}/activity', 'activity');
                Route::get('/{id}/flags', 'flags');
                Route::post('/{id}/flags', 'addFlag');
                Route::delete('/{id}/flags/{flagType}', 'removeFlag');
                Route::get('/{id}/jobs', 'jobs');
                Route::get('/{id}/job-history', 'jobHistory');
            });

            // Activity Logs (Admin)
            Route::prefix('activity')->controller(\App\Core\Http\Controllers\Admin\ActivityLogController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/recent', 'recent');
                Route::get('/user/{userId}', 'userActivity');
                Route::get('/suspicious', 'suspicious');
                Route::post('/clean', 'clean');
            });

            // Cache Management
            Route::prefix('cache')->controller(\App\Core\Http\Controllers\Admin\CacheController::class)->group(function () {
                Route::post('/clear', 'clear');
                Route::post('/clear-user/{userId}', 'clearUser');
                Route::post('/warm-up', 'warmUp');
            });

            // Staff Chat
            Route::prefix('staff-chat')->controller(\App\Core\Http\Controllers\Admin\StaffChatController::class)->group(function () {
                Route::get('/messages', 'messages');
                Route::post('/messages', 'send');
                Route::get('/unread', 'unread');
            });

            // Backup Management
            Route::prefix('backups')->controller(\App\Core\Http\Controllers\Admin\BackupController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/settings', 'settings');
                Route::put('/settings', 'updateSettings');
                Route::put('/storage', 'updateStorage');
                Route::post('/test-storage', 'testStorage');
                Route::post('/', 'store');
                Route::get('/{id}/download', 'download');
                Route::post('/{id}/restore', 'restore');
                Route::delete('/{id}', 'destroy');
            });

            // System Health Monitoring
            Route::prefix('system')->controller(\App\Core\Http\Controllers\Admin\SystemHealthController::class)->group(function () {
                Route::get('/health', 'index');
                Route::post('/queue/retry-failed', 'retryFailedJobs');
                Route::post('/cache/clear', 'clearCache');
            });

            // Developer Tools — hook registry & metrics introspection
            Route::prefix('developer')->controller(\App\Core\Http\Controllers\Admin\DeveloperController::class)->group(function () {
                Route::get('/hooks', 'hooks');
                Route::get('/metrics', 'metrics');
            });

            // API Keys Management
            Route::prefix('api-keys')->controller(\App\Core\Http\Controllers\Admin\ApiKeyController::class)->group(function () {
                Route::get('/', 'index');
                Route::get('/permissions', 'permissions');
                Route::get('/analytics', 'analytics');
                Route::post('/', 'store');
                Route::get('/{id}', 'show');
                Route::patch('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
                Route::post('/{id}/toggle', 'toggle');
                Route::post('/{id}/regenerate-secret', 'regenerateSecret');
                Route::get('/{id}/logs', 'logs');
            });

            // Account Duplication Detection
            Route::get('/account-duplication', [\App\Core\Http\Controllers\Admin\AccountDuplicationController::class, 'index']);

            // Membership Management
            Route::prefix('membership')->controller(\App\Core\Http\Controllers\Admin\MembershipAdminController::class)->group(function () {
                Route::get('/settings', 'settings');
                Route::post('/settings/toggle', 'toggle');
                Route::patch('/plans/{plan}', 'updatePlan');
            });

            // Admin Sidebar (for dynamic menu loading)
            Route::get('/sidebar', function () {
                $user = request()->user();
                $menu = \App\Core\Admin\AdminSidebarService::getSidebarItems($user);
                return response()->json(['menu' => $menu]);
            });
        });
    });

    // Note: routes/admin.php is no longer needed - all admin routes are now in api.php
    // require base_path('routes/admin.php');

    Route::middleware('auth:sanctum')->group(function () {
        // Emoji Routes
        Route::get('/emojis', [EmojiController::class, 'index']);
        Route::get('/emojis/quick-reactions', [EmojiController::class, 'quickReactions']);
        Route::get('/emojis/search', [EmojiController::class, 'search']);

        // Text Formatter Routes (BBCode & JoyPixels)
        Route::post('/format/preview', [\App\Core\Http\Controllers\TextFormatterController::class, 'preview']);
        Route::get('/format/bbcodes', [\App\Core\Http\Controllers\TextFormatterController::class, 'bbcodes']);
        Route::get('/format/emojis', [\App\Core\Http\Controllers\TextFormatterController::class, 'emojis']);
        Route::post('/format/plain', [\App\Core\Http\Controllers\TextFormatterController::class, 'plain']);
        Route::get('/format/emoji/search', [\App\Core\Http\Controllers\TextFormatterController::class, 'searchEmoji']);

        // Dashboard
        Route::get('/dashboard', [\App\Core\Http\Controllers\DashboardController::class, 'index']);

        // Player Profile
        Route::get('/player/{id}', [\App\Core\Http\Controllers\ProfileController::class, 'show']);

        // Player Statistics
        Route::prefix('stats')->controller(\App\Core\Http\Controllers\PlayerStatsController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/player/{userId}', 'show');
            Route::post('/refresh', 'refresh');
        });

        // Activity Logs (Player's own)
        Route::get('/activity', [\App\Core\Http\Controllers\ActivityController::class, 'myActivity']);
        Route::get('/activity/my-activity', [\App\Core\Http\Controllers\ActivityController::class, 'myActivity']);

        // Notifications
        Route::prefix('notifications')->controller(\App\Core\Http\Controllers\NotificationController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/recent', 'recent');
            Route::get('/unread-count', 'unreadCount');
            Route::post('/{id}/read', 'markAsRead');
            Route::post('/mark-all-read', 'markAllAsRead');
            Route::delete('/{id}', 'delete');
            Route::delete('/read/clear', 'deleteRead');
        });


        // NOTE: Gaming routes (crimes, gym, hospital, bank, drugs, jail, inventory,
        // combat, theft, racing, properties, bounties, missions, detective, gangs,
        // organized-crime, travel, shop) are now provided by plugins.
        // Install the gaming bundle to restore these features.
    });

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Emoji Routes
        Route::get('/emojis', [EmojiController::class, 'index']);
        Route::get('/emojis/quick-reactions', [EmojiController::class, 'quickReactions']);
        Route::get('/emojis/search', [EmojiController::class, 'search']);

        // Text Formatter Routes (BBCode & JoyPixels)
        Route::post('/format/preview', [\App\Core\Http\Controllers\TextFormatterController::class, 'preview']);
        Route::get('/format/bbcodes', [\App\Core\Http\Controllers\TextFormatterController::class, 'bbcodes']);
        Route::get('/format/emojis', [\App\Core\Http\Controllers\TextFormatterController::class, 'emojis']);
        Route::post('/format/plain', [\App\Core\Http\Controllers\TextFormatterController::class, 'plain']);
        Route::get('/format/emoji/search', [\App\Core\Http\Controllers\TextFormatterController::class, 'searchEmoji']);

        // Dashboard
        Route::get('/dashboard', [\App\Core\Http\Controllers\DashboardController::class, 'index']);

        // Player Profile
        Route::get('/player/{id}', [\App\Core\Http\Controllers\ProfileController::class, 'show']);

        // Player Statistics
        Route::prefix('stats')->controller(\App\Core\Http\Controllers\PlayerStatsController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/player/{userId}', 'show');
            Route::post('/refresh', 'refresh');
        });

        // Activity Logs (Player's own)
        Route::get('/activity', [\App\Core\Http\Controllers\ActivityController::class, 'myActivity']);
        Route::get('/activity/my-activity', [\App\Core\Http\Controllers\ActivityController::class, 'myActivity']);

        // Notifications
        Route::prefix('notifications')->controller(\App\Core\Http\Controllers\NotificationController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/recent', 'recent');
            Route::get('/unread-count', 'unreadCount');
            Route::post('/{id}/read', 'markAsRead');
            Route::post('/mark-all-read', 'markAllAsRead');
            Route::delete('/{id}', 'delete');
            Route::delete('/read/clear', 'deleteRead');
        });

        // Membership routes (authenticated)
        Route::prefix('membership')->controller(\App\Core\Http\Controllers\MembershipController::class)->group(function () {
            Route::get('/status', 'status');
            Route::post('/stripe/create-checkout', 'createStripeCheckout');
            Route::post('/paypal/create-order', 'createPayPalOrder');
            Route::post('/cancel', 'cancel');
        });

        // NOTE: Gaming routes (crimes, gym, hospital, bank, drugs, jail, inventory,
        // combat, theft, racing, properties, bounties, missions, detective, gangs,
        // organized-crime, travel, shop) are now provided by plugins.
        // Install the gaming bundle to restore these features.
    });

}); // end prefix('v1')

// Webhook routes (no auth — Stripe/PayPal call these)
Route::post('stripe/webhook', [\App\Core\Http\Controllers\MembershipController::class, 'handleStripeWebhook']);
Route::post('paypal/webhook', [\App\Core\Http\Controllers\MembershipController::class, 'handlePayPalWebhook']);
