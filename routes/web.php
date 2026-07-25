<?php

use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\SuperAdmin;
use App\Http\Controllers\Web\SuperAdmin\CouponController as SuperAdminCouponController;
use App\Http\Controllers\Web\Tenant;
use App\Http\Controllers\Web\Tenant\ScreenshotController;
use App\Http\Controllers\Web\Tenant\SubscriptionController;
use App\Http\Controllers\Web\SubscriptionWebhookController;
use App\Http\Controllers\Web\InstagramWebhookController;
use App\Http\Controllers\Web\WhatsappWebhookController;
use App\Http\Controllers\Web\LeadWebhookController;
use App\Http\Controllers\Web\SuperAdmin\LeadIntegrationController as SuperAdminLeadIntegrationController;
use App\Http\Controllers\Web\SuperAdmin\PlatformSettingController as SuperAdminPlatformSettingController;
use App\Http\Controllers\Web\Tenant\LeadIntegrationController as TenantLeadIntegrationController;
use Illuminate\Support\Facades\Route;
use App\Models\DeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
// ══════════════════════════════════════════════════════════════════
// PUBLIC — Auth routes (base domain: saas-crm.test)
// ══════════════════════════════════════════════════════════════════

Route::get('/', fn() => view('welcome'))->name('home');
Route::get('/privacy-policy', fn() => view('legal.privacy-policy'))->name('privacy-policy');
Route::get('/firebase-test', function (\Illuminate\Http\Request $request, Messaging $messaging) {
    try {
        $token = $request->query('token')
            ?? DeviceToken::where('is_active', true)->latest('last_used_at')->value('device_token');

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'No active device token found. Register a device first or pass ?token=',
            ], 422);
        }

        $message = CloudMessage::new()
            ->withToken($token)
            ->withNotification(FirebaseNotification::create(
                'Firebase Test',
                'This is a real push notification sent from /firebase-test.'
            ));

        $messaging->send($message);

        return response()->json([
            'status' => true,
            'message' => 'Notification sent successfully',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }
});
Route::middleware('auth')->post('/device-token', [DeviceTokenController::class, 'store']);
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register',  [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/forgot-password',  [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password',        [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

// ══════════════════════════════════════════════════════════════════
// RAZORPAY WEBHOOK (no CSRF, no auth — Razorpay se aata hai)
// ══════════════════════════════════════════════════════════════════

Route::post('/webhook/razorpay', [SubscriptionWebhookController::class, 'handle'])
    ->name('webhook.razorpay');

// ══════════════════════════════════════════════════════════════════
// META WEBHOOKS — Instagram & WhatsApp (no CSRF, no auth)
// ══════════════════════════════════════════════════════════════════

Route::get('/webhook/instagram',  [InstagramWebhookController::class, 'verify'])->name('webhook.instagram.verify');
Route::post('/webhook/instagram', [InstagramWebhookController::class, 'handle'])->name('webhook.instagram');

Route::get('/webhook/whatsapp',   [WhatsappWebhookController::class, 'verify'])->name('webhook.whatsapp.verify');
Route::post('/webhook/whatsapp',  [WhatsappWebhookController::class, 'handle'])->name('webhook.whatsapp');

// ── Lead Source Webhooks (Meta, JustDial, TradeIndia, Sulekha) ────
Route::get('/webhook/leads/{token}',  [LeadWebhookController::class, 'verify'])->name('webhook.leads.verify');
Route::post('/webhook/leads/{token}', [LeadWebhookController::class, 'handle'])->name('webhook.leads');

// ── Instagram OAuth (no auth — phone browser redirected here by Meta) ──
Route::get('/instagram/oauth/start',       [Tenant\InstagramController::class, 'oauthStart'])->name('instagram.oauth.start');
Route::get('/instagram/oauth/callback',    [Tenant\InstagramController::class, 'oauthCallback'])->name('instagram.oauth.callback');
Route::get('/instagram/oauth/select-page', [Tenant\InstagramController::class, 'oauthSelectPage'])->name('instagram.oauth.select-page');

// ── WhatsApp OAuth (no auth — phone browser redirected here by Meta) ──
Route::get('/whatsapp/oauth/start',    [Tenant\WhatsappChatbotController::class, 'oauthStart'])->name('whatsapp.oauth.start');
Route::get('/whatsapp/oauth/callback', [Tenant\WhatsappChatbotController::class, 'oauthCallback'])->name('whatsapp.oauth.callback');

// ══════════════════════════════════════════════════════════════════
// SUPER ADMIN (base domain: saas-crm.test/superadmin)
// ══════════════════════════════════════════════════════════════════

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {

        Route::get('/dashboard', [SuperAdmin\DashboardController::class, 'index'])
            ->name('dashboard');

        // Tenant management
        Route::prefix('tenants')->name('tenants.')->controller(SuperAdmin\TenantController::class)->group(function () {
            Route::get('/',                       'index')->name('index');
            Route::get('/{tenant}',               'show')->name('show');
            Route::post('/{tenant}/toggle-status', 'toggleStatus')->name('toggle-status');
        });

        // Plan management
        Route::prefix('plans')->name('plans.')->controller(SuperAdmin\PlanController::class)->group(function () {
            Route::get('/',                'index')->name('index');
            Route::get('/create',          'create')->name('create');
            Route::post('/',               'store')->name('store');
            Route::get('/{plan}/edit',     'edit')->name('edit');
            Route::put('/{plan}',          'update')->name('update');
            Route::delete('/{plan}',       'destroy')->name('destroy');
            Route::post('/{plan}/toggle',  'toggle')->name('toggle');
        });

        // Lead Integration access control
        Route::prefix('lead-integrations')->name('lead-integrations.')->controller(SuperAdminLeadIntegrationController::class)->group(function () {
            Route::get('/',                          'index')->name('index');
            Route::get('/{tenant}/edit',             'edit')->name('edit');
            Route::put('/{tenant}',                  'update')->name('update');
            Route::post('/{tenant}/toggle',          'toggle')->name('toggle');
        });

        // Platform-level settings (Meta App credentials etc.)
        Route::prefix('platform-settings')->name('platform-settings.')->controller(SuperAdminPlatformSettingController::class)->group(function () {
            Route::get('/meta',  'metaApp')->name('meta');
            Route::post('/meta', 'saveMetaApp')->name('meta.save');
        });

        // Coupon management
        Route::prefix('coupons')->name('coupons.')->controller(SuperAdminCouponController::class)->group(function () {
            Route::get('/',           'index')->name('index');
            Route::get('/create',     'create')->name('create');
            Route::post('/',          'store')->name('store');
            Route::get('/{coupon}/edit',  'edit')->name('edit');
            Route::put('/{coupon}',       'update')->name('update');
            Route::delete('/{coupon}',    'destroy')->name('destroy');
            Route::post('/{coupon}/toggle', 'toggle')->name('toggle');
        });

        // Error logs monitoring
        Route::prefix('error-logs')->name('error-logs.')->controller(SuperAdmin\ErrorLogController::class)->group(function () {
            Route::get('/',                       'index')->name('index');
            Route::get('/{errorLog}',             'show')->name('show');
            Route::post('/{errorLog}/resolve',    'resolve')->name('resolve');
            Route::post('/resolve-all',           'resolveAll')->name('resolve-all');
            Route::delete('/{errorLog}',          'destroy')->name('destroy');
        });

        // Workflow templates
        Route::prefix('workflow-templates')->name('workflow-templates.')->controller(SuperAdmin\WorkflowTemplateController::class)->group(function () {
            Route::get('/',                              'index')->name('index');
            Route::get('/create',                        'create')->name('create');
            Route::post('/',                             'store')->name('store');
            Route::get('/{workflowTemplate}/edit',       'edit')->name('edit');
            Route::put('/{workflowTemplate}',            'update')->name('update');
            Route::delete('/{workflowTemplate}',         'destroy')->name('destroy');
            Route::post('/{workflowTemplate}/toggle',    'toggle')->name('toggle');
        });

        // Workflow requests from tenants
        Route::prefix('workflow-requests')->name('workflow-requests.')->controller(SuperAdmin\WorkflowRequestController::class)->group(function () {
            Route::get('/',                              'index')->name('index');
            Route::get('/{workflowRequest}',             'show')->name('show');
            Route::patch('/{workflowRequest}/status',    'updateStatus')->name('update-status');
        });

        // Tenant webhook management
        Route::prefix('tenants/{tenant}/webhooks')->name('tenant-webhooks.')->controller(SuperAdmin\TenantWebhookController::class)->group(function () {
            Route::get('/',                       'index')->name('index');
            Route::post('/',                      'store')->name('store');
            Route::patch('/{webhook}',            'update')->name('update');
            Route::delete('/{webhook}',           'destroy')->name('destroy');
            Route::post('/{webhook}/toggle',      'toggle')->name('toggle');
            Route::post('/{webhook}/test',        'test')->name('test');
            Route::post('/regenerate-token',      'regenerateToken')->name('regenerate-token');
        });
    });

// ══════════════════════════════════════════════════════════════════
// TENANT ROUTES (subdomain: {tenant}.saas-crm.test)
//
// Route::domain() use karte hain — {tenant} parameter automatically
// aata hai lekin Lead/Contact model injection ke liye
// hume explicit binding use karni padegi
// ══════════════════════════════════════════════════════════════════

// ── Subscription routes outside subscription middleware ────────────
// (expired page + verify must be accessible even when sub is expired)
Route::middleware(['tenant', 'auth'])
    ->name('tenant.')
    ->group(function () {
        Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])->name('subscription.expired');
        Route::get('/subscription/plans',   [SubscriptionController::class, 'plans'])->name('subscription.plans');
        Route::get('/subscription/current', [SubscriptionController::class, 'current'])->name('subscription.current');
        Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
        Route::get('/subscription/checkout/{plan}/{cycle}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::post('/subscription/verify',       [SubscriptionController::class, 'verify'])->name('subscription.verify');
        Route::post('/subscription/cancel',       [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::post('/subscription/apply-coupon', [SubscriptionController::class, 'applyCoupon'])->name('subscription.apply-coupon');
    });

// Route::domain('{tenant}.' . config('app.base_domain', 'saas-crm.test'))
Route::middleware(['tenant', 'auth', 'subscription'])
    ->name('tenant.')
    ->group(function () {

        // ── Dashboard ─────────────────────────────────────────────
        Route::get('/dashboard', [Tenant\DashboardController::class, 'index'])
            ->name('dashboard');

        // ── Lead Integrations ─────────────────────────────────────
        Route::prefix('lead-integrations')->name('lead-integrations.')->controller(TenantLeadIntegrationController::class)->group(function () {
            Route::get('/',                      'index')->name('index');
            Route::get('/{platform}/setup',      'setup')->name('setup');
            Route::post('/{platform}/save',      'save')->name('save');
            Route::post('/{platform}/regenerate', 'regenerateToken')->name('regenerate');
            Route::post('/{platform}/sync',      'syncNow')->name('sync');
            Route::get('/{platform}/test',       'testConnection')->name('test');
        });

        // ── Leads ─────────────────────────────────────────────────
        // Explicit routes instead of resource() to avoid
        // model injection conflict with {tenant} parameter
        Route::get('/leads',            [Tenant\LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/create',     [Tenant\LeadController::class, 'create'])->name('leads.create');
        Route::post('/leads',           [Tenant\LeadController::class, 'store'])->name('leads.store');
        Route::get('/leads/{id}',       [Tenant\LeadController::class, 'show'])->name('leads.show');
        Route::get('/leads/{id}/edit',  [Tenant\LeadController::class, 'edit'])->name('leads.edit');
        Route::put('/leads/{id}',       [Tenant\LeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{id}',    [Tenant\LeadController::class, 'destroy'])->name('leads.destroy');

        // Lead extra actions
        Route::post('/leads/{lead}/assign',  [Tenant\LeadController::class, 'assign'])->name('leads.assign');
        Route::post('/leads/{lead}/convert', [Tenant\LeadController::class, 'convert'])->name('leads.convert');
        Route::post('/leads/{lead}/status',  [Tenant\LeadController::class, 'updateStatus'])->name('leads.status');
        Route::get('/leads/{id}/data',     [Tenant\LeadController::class, 'leadData'])->name('leads.data');
        Route::post('/leads/save-view', [Tenant\LeadController::class, 'saveView'])->name('leads.view');
        Route::patch('/leads/{id}/status',  [Tenant\LeadController::class, 'updateStatus'])->name('leads.status.update');
        Route::post('/leads/bulk-status',       [Tenant\LeadController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::post('/leads/{lead}/call-log',   [Tenant\LeadController::class, 'storeCallLog'])->name('leads.call-log.store');
        // ── Follow-ups ────────────────────────────────────────────
        Route::get('/followups',           [Tenant\FollowupController::class, 'index'])->name('followups.index');
        Route::get('/followups/create',    [Tenant\FollowupController::class, 'create'])->name('followups.create');
        Route::post('/followups',          [Tenant\FollowupController::class, 'store'])->name('followups.store');
        Route::get('/followups/{followup}',      [Tenant\FollowupController::class, 'show'])->name('followups.show');
        Route::get('/followups/{followup}/edit', [Tenant\FollowupController::class, 'edit'])->name('followups.edit');
        Route::put('/followups/{followup}',      [Tenant\FollowupController::class, 'update'])->name('followups.update');
        Route::delete('/followups/{followup}',   [Tenant\FollowupController::class, 'destroy'])->name('followups.destroy');

        Route::post('/followups/{followup}/done',   [Tenant\FollowupController::class, 'markDone'])->name('followups.done');
        Route::post('/followups/{followup}/missed', [Tenant\FollowupController::class, 'markMissed'])->name('followups.missed');

        Route::post('/followups/{followup}/attachments', [Tenant\FollowupController::class, 'storeAttachment'])->name('followups.attachments.store');
        Route::delete('/followups/{followup}/attachments/{attachment}', [Tenant\FollowupController::class, 'destroyAttachment'])->name('followups.attachments.destroy');

        // ── Contacts (uncomment when ready) ───────────────────────
        Route::prefix('/contacts')->name('contacts.')->group(function () {
            Route::controller(Tenant\ContactController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::get('/{id}/report', 'customerReport')->name('report');
                //    search customer
                Route::get('/search', 'searchCustomers')->name('search');
            });
        });
        // ── Deals (uncomment when ready) ──────────────────────────
        // Route::get('/deals', ...)->name('deals.index');

        Route::prefix('/deals')->name('deals.')->group(function () {
            Route::controller(Tenant\DealController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/pipeline', 'pipelineAnalytics')->name('pipeline');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::patch('/update-stage/{id}', 'updateStage')->name('update_stage');
                Route::post('/{id}/mark-won',  'markWon')->name('mark_won');
                Route::post('/{id}/mark-lost', 'markLost')->name('mark_lost');
            });
        });

        // Quotations routes
        Route::prefix('/quotations')->name('quotations.')->group(function () {
            Route::controller(Tenant\QuotationController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/status', 'updateStatus')->name('update_status');
                Route::get('/{id}/pdf', 'pdf')->name('pdf');
                Route::post('/{id}/convert', 'convertToInvoice')->name('convert');
                Route::get('/{id}/data', 'quotationData')->name('data');
            });
        });


        // Invoices routes
        Route::prefix('/invoices')->name('invoices.')->group(function () {
            Route::controller(Tenant\InvoiceController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');

                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/status', 'updateStatus')->name('update_status');
                Route::get('/{id}/pdf', 'pdf')->name('pdf');
            });
        });

        // Products / Item Catalog routes
        Route::prefix('/products')->name('products.')->group(function () {
            Route::controller(Tenant\ProductController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/search', 'search')->name('search');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        });

        //Tasks routes
        Route::prefix('/tasks')->name('tasks.')->group(function () {
            Route::controller(Tenant\TaskController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::patch('/{id}/update-stage', 'updateStatus')->name('update_stage');
            });
        });

        Route::prefix('/staffs')->name('staffs.')->group(function () {
            Route::controller(Tenant\StaffController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/activate', 'activate')->name('activate');
                Route::post('/{id}/deactivate', 'deactivate')->name('deactivate');
            });
        });

        Route::prefix('/departments')->name('departments.')->group(function () {
            Route::controller(Tenant\DepartmentController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        });

        // Attendance routes
        Route::prefix('/attendances')->name('attendances.')->group(function () {
            Route::controller(Tenant\AttendanceController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                // Route::get('/{attendance}', 'show')->name('show');
                Route::get('/{attendance}/edit', 'edit')->name('edit');
                Route::put('/{attendance}', 'update')->name('update');
                Route::delete('/{attendance}', 'destroy')->name('destroy');
                // bulk attendance upload route
                Route::get('/bulk-upload', 'bulk')->name('bulk');
                Route::post('/bulk-upload', 'bulkStore')->name('bulk.store');
                // clock in/out routes
                Route::get('/clock', 'clockView')->name('clock');
                Route::post('/clock-in', 'clockIn')->name('clock.in');
                Route::post('/clock-out', 'clockOut')->name('clock.out');
            });
        });

        // Attendance screenshots routes
        Route::prefix('/screenshots')->name('screenshots.')->group(function () {
            Route::controller(Tenant\ScreenshotController::class)->group(function () {
                Route::post('/upload', 'upload')->name('upload');

                // Admin: view & delete
                Route::get('/attendance/{attendance}/screenshots', 'show')
                    ->name('show');

                Route::delete('/{screenshot}', 'destroy')
                    ->name('destroy');
            });
        });

        // ── Instagram Automation (tenant_admin only) ──────────────
        Route::prefix('instagram')->name('instagram.')->middleware(['role:tenant_admin'])->group(function () {
            Route::get('/',                                [Tenant\InstagramController::class, 'index'])->name('index');
            Route::get('/settings',                        [Tenant\InstagramController::class, 'settings'])->name('settings');
            Route::post('/settings',                       [Tenant\InstagramController::class, 'saveSettings'])->name('settings.save');
            Route::post('/test-connection',                [Tenant\InstagramController::class, 'testConnection'])->name('test-connection');

            // Automations
            Route::get('/automations',                     [Tenant\InstagramController::class, 'automations'])->name('automations');
            Route::get('/automations/create',              [Tenant\InstagramController::class, 'createAutomation'])->name('automations.create');
            Route::get('/automations/posts',               [Tenant\InstagramController::class, 'fetchPosts'])->name('automations.posts');
            Route::post('/automations',                    [Tenant\InstagramController::class, 'storeAutomation'])->name('automations.store');
            Route::get('/automations/{id}/edit',           [Tenant\InstagramController::class, 'editAutomation'])->name('automations.edit');
            Route::put('/automations/{id}',                [Tenant\InstagramController::class, 'updateAutomation'])->name('automations.update');
            Route::post('/automations/{id}/toggle',        [Tenant\InstagramController::class, 'toggleAutomation'])->name('automations.toggle');
            Route::delete('/automations/{id}',             [Tenant\InstagramController::class, 'destroyAutomation'])->name('automations.destroy');

            // Chatbot flows
            Route::get('/chatbot',                         [Tenant\InstagramController::class, 'chatbot'])->name('chatbot');
            Route::post('/chatbot',                        [Tenant\InstagramController::class, 'storeChatbotFlow'])->name('chatbot.store');
            Route::put('/chatbot/{id}',                    [Tenant\InstagramController::class, 'updateChatbotFlow'])->name('chatbot.update');
            Route::post('/chatbot/{id}/toggle',            [Tenant\InstagramController::class, 'toggleChatbotFlow'])->name('chatbot.toggle');
            Route::delete('/chatbot/{id}',                 [Tenant\InstagramController::class, 'destroyChatbotFlow'])->name('chatbot.destroy');

            // Logs
            Route::get('/logs',                            [Tenant\InstagramController::class, 'logs'])->name('logs');

            // Guide / How it works
            Route::get('/guide',                           [Tenant\InstagramController::class, 'guide'])->name('guide');

            // OAuth QR Connect
            Route::get('/oauth/qr',     [Tenant\InstagramController::class, 'oauthGenerateQr'])->name('oauth.qr');
            Route::get('/oauth/status', [Tenant\InstagramController::class, 'oauthStatus'])->name('oauth.status');
        });

        // Email and WhatsApp templates and logs
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/',                    [Tenant\WhatsappController::class, 'index'])->name('index');
            Route::get('templates',            [Tenant\WhatsappController::class, 'templates'])->name('templates');
            Route::post('templates',           [Tenant\WhatsappController::class, 'storeTemplate'])->name('templates.store');
            Route::put('templates/{id}',       [Tenant\WhatsappController::class, 'updateTemplate'])->name('templates.update');
            Route::delete('templates/{id}',    [Tenant\WhatsappController::class, 'deleteTemplate'])->name('templates.delete');
            Route::get('send',                 [Tenant\WhatsappController::class, 'sendForm'])->name('send');
            Route::post('send',                [Tenant\WhatsappController::class, 'send'])->name('send.store');
            Route::get('bulk',                 [Tenant\WhatsappController::class, 'bulkForm'])->name('bulk');
            Route::post('bulk',                [Tenant\WhatsappController::class, 'sendBulk'])->name('bulk.send');
            Route::get('logs',                 [Tenant\WhatsappController::class, 'logs'])->name('logs');
            Route::post('preview-template',    [Tenant\WhatsappController::class, 'previewTemplate'])->name('preview');

            // WhatsApp Chatbot & Business API settings
            Route::get('chatbot',                   [Tenant\WhatsappChatbotController::class, 'flows'])->name('chatbot');
            Route::post('chatbot',                  [Tenant\WhatsappChatbotController::class, 'storeFlow'])->name('chatbot.store');
            Route::put('chatbot/{id}',              [Tenant\WhatsappChatbotController::class, 'updateFlow'])->name('chatbot.update');
            Route::post('chatbot/{id}/toggle',      [Tenant\WhatsappChatbotController::class, 'toggleFlow'])->name('chatbot.toggle');
            Route::delete('chatbot/{id}',           [Tenant\WhatsappChatbotController::class, 'destroyFlow'])->name('chatbot.destroy');
            Route::get('api-settings',              [Tenant\WhatsappChatbotController::class, 'settings'])->name('api-settings');
            Route::post('api-settings',             [Tenant\WhatsappChatbotController::class, 'saveSettings'])->name('api-settings.save');
            Route::post('api-settings/test',        [Tenant\WhatsappChatbotController::class, 'testConnection'])->name('api-settings.test');
            Route::get('oauth/qr',                  [Tenant\WhatsappChatbotController::class, 'oauthGenerateQr'])->name('oauth.qr');
            Route::get('oauth/status',              [Tenant\WhatsappChatbotController::class, 'oauthStatus'])->name('oauth.status');
        });

        // Email
        Route::prefix('email')->name('email.')->group(function () {
            Route::get('/',                    [Tenant\EmailController::class, 'index'])->name('index');
            Route::get('templates',            [Tenant\EmailController::class, 'templates'])->name('templates');
            Route::post('templates',           [Tenant\EmailController::class, 'storeTemplate'])->name('templates.store');
            Route::put('templates/{id}',       [Tenant\EmailController::class, 'updateTemplate'])->name('templates.update');
            Route::delete('templates/{id}',    [Tenant\EmailController::class, 'deleteTemplate'])->name('templates.delete');
            Route::get('send',                 [Tenant\EmailController::class, 'sendForm'])->name('send');
            Route::post('send',                [Tenant\EmailController::class, 'send'])->name('send.store');
            Route::get('bulk',                 [Tenant\EmailController::class, 'bulkForm'])->name('bulk');
            Route::post('bulk',                [Tenant\EmailController::class, 'sendBulk'])->name('bulk.send');
            Route::get('logs',                 [Tenant\EmailController::class, 'logs'])->name('logs');
            Route::post('preview-template',    [Tenant\EmailController::class, 'previewTemplate'])->name('preview');

            // SMTP connect settings (tenant_admin only)
            Route::get('settings',             [Tenant\EmailController::class, 'settings'])->name('settings')->middleware(['role:tenant_admin']);
            Route::post('settings',            [Tenant\EmailController::class, 'saveSettings'])->name('settings.save')->middleware(['role:tenant_admin']);
            Route::post('settings/test',       [Tenant\EmailController::class, 'testConnection'])->name('settings.test')->middleware(['role:tenant_admin']);
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/overview', [Tenant\ReportController::class, 'overview'])->name('overview');
            Route::get('/deals',    [Tenant\ReportController::class, 'deals'])->name('deals');
            Route::get('/revenue',  [Tenant\ReportController::class, 'revenue'])->name('revenue');
            Route::get('/staff',    [Tenant\ReportController::class, 'staff'])->name('staff');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/',              [Tenant\NotificationController::class, 'index'])->name('index');
            Route::get('/latest',        [Tenant\NotificationController::class, 'latest'])->name('latest');
            Route::post('/{id}/read',    [Tenant\NotificationController::class, 'markRead'])->name('read');
            Route::post('/read-all',     [Tenant\NotificationController::class, 'markAllRead'])->name('read-all');
            Route::delete('/{id}',       [Tenant\NotificationController::class, 'destroy'])->name('destroy');
            Route::post('/clear',        [Tenant\NotificationController::class, 'clearRead'])->name('clear');
            Route::get('/preferences',   [Tenant\NotificationController::class, 'preferences'])->name('preferences');
            Route::post('/preferences',  [Tenant\NotificationController::class, 'savePreferences'])->name('preferences.save');
        });

        // Settings and profile
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/',        [Tenant\SettingsController::class, 'index'])->name('index');
            Route::put('profile',  [Tenant\SettingsController::class, 'updateProfile'])->name('profile');
            Route::put('password', [Tenant\SettingsController::class, 'updatePassword'])->name('password');
            Route::post('avatar',  [Tenant\SettingsController::class, 'uploadAvatar'])->name('avatar');
            Route::put('company',  [Tenant\SettingsController::class, 'updateCompany'])->name('company');
        });

        // My Profile
        Route::get('profile', [Tenant\SettingsController::class, 'profile'])->name('profile.show');


        // custom fields
        Route::prefix('custom-fields')->name('custom-fields.')->group(function () {
            Route::get('/',                      [Tenant\CustomFieldController::class, 'index'])->name('index');
            Route::get('/{module}',              [Tenant\CustomFieldController::class, 'module'])->name('module');
            Route::get('/{module}/create',       [Tenant\CustomFieldController::class, 'create'])->name('create');
            Route::post('/{module}',             [Tenant\CustomFieldController::class, 'store'])->name('store');
            Route::get('/{module}/{id}/edit',    [Tenant\CustomFieldController::class, 'edit'])->name('edit');
            Route::put('/{module}/{id}',         [Tenant\CustomFieldController::class, 'update'])->name('update');
            Route::post('/{id}/toggle',          [Tenant\CustomFieldController::class, 'toggle'])->name('toggle');
            Route::post('/reorder',              [Tenant\CustomFieldController::class, 'reorder'])->name('reorder');
            Route::delete('/{id}',               [Tenant\CustomFieldController::class, 'destroy'])->name('destroy');
        });

        // Tenant Field Manager — sirf tenant_admin
        Route::prefix('tenant-fields')->name('tenant-fields.')
            ->middleware(['role:tenant_admin'])
            ->group(function () {
                Route::get('/',                   [Tenant\TenantFieldController::class, 'index'])->name('index');
                Route::get('/{module}',           [Tenant\TenantFieldController::class, 'module'])->name('module');
                Route::post('/{module}/global',   [Tenant\TenantFieldController::class, 'addGlobal'])->name('add-global');
                Route::post('/{module}/custom',   [Tenant\TenantFieldController::class, 'addCustom'])->name('add-custom');
                Route::post('/{id}',              [Tenant\TenantFieldController::class, 'update'])->name('update');
                Route::post('/{id}/toggle',       [Tenant\TenantFieldController::class, 'toggle'])->name('toggle');
                Route::post('/reorder',           [Tenant\TenantFieldController::class, 'reorder'])->name('reorder');
                Route::delete('/{id}',            [Tenant\TenantFieldController::class, 'remove'])->name('remove');
            });

        // Audit Logs — users with audit_logs.view permission (tenant_admin gets it by default)
        Route::prefix('audit-logs')->name('audit-logs.')->middleware(['permission:audit_logs.view'])
            ->controller(Tenant\AuditLogController::class)->group(function () {
                Route::get('/',      'index')->name('index');
                Route::get('/{id}',  'show')->name('show');
            });

        // tenant staff roles and permission
        Route::prefix('roles')->name('roles.')->controller(Tenant\RoleController::class)->middleware(['role:tenant_admin'])->group(function () {

            Route::get('/',              'index')->name('index');
            Route::get('/create',        'create')->name('create');
            Route::post('/',             'store')->name('store');
            Route::get('/{id}',          'show')->name('show');
            Route::get('/{id}/edit',     'edit')->name('edit');
            Route::put('/{id}',          'update')->name('update');
            Route::delete('/{id}',       'destroy')->name('destroy');

            // Get role permissions (for copy-from AJAX)
            Route::get('/{id}/permissions', function ($id) {
                $role = \Spatie\Permission\Models\Role::findOrFail($id);
                return response()->json([
                    'permission_ids' => $role->permissions->pluck('id'),
                ]);
            })->name('permissions');

            // Assign role to specific user
            Route::post('/assign',  'assignToUser')->name('assign');
        });

        // ── API Key Management (tenant_admin only) ────────────────
        Route::prefix('api-keys')->name('api-keys.')->middleware(['role:tenant_admin'])
            ->controller(Tenant\ApiKeyController::class)->group(function () {
                Route::get('/',              'index')->name('index');
                Route::post('/',             'store')->name('store');
                Route::post('/{id}/toggle',  'toggle')->name('toggle');
                Route::post('/{id}/regenerate', 'regenerate')->name('regenerate');
                Route::delete('/{id}',       'destroy')->name('destroy');
            });

        // ── Webhooks — n8n automation (tenant_admin only) ──────────
        Route::prefix('webhooks')->name('webhooks.')->middleware(['role:tenant_admin'])
            ->controller(Tenant\WebhookController::class)->group(function () {
                Route::get('/',                  'index')->name('index');
                Route::post('/',                 'store')->name('store');
                Route::patch('/{webhook}',       'update')->name('update');
                Route::delete('/{webhook}',      'destroy')->name('destroy');
                Route::post('/{webhook}/toggle', 'toggle')->name('toggle');
                Route::post('/{webhook}/test',   'test')->name('test');
                Route::post('/regenerate-token', 'regenerateToken')->name('regenerate-token');
            });

        // ── Slack Notifications (tenant_admin only) ────────────────
        Route::prefix('slack')->name('slack.')->middleware(['role:tenant_admin'])
            ->controller(Tenant\SlackController::class)->group(function () {
                Route::get('/',           'index')->name('index');
                Route::post('/',          'store')->name('store');
                Route::post('/toggle',    'toggle')->name('toggle');
                Route::post('/test',      'test')->name('test');
                Route::delete('/',        'destroy')->name('destroy');
            });

        // ── AI & Workflow Automation ───────────────────────────────
        Route::get('/automation',         [Tenant\AutomationController::class, 'index'])->name('automation.index');
        Route::post('/automation/request', [Tenant\AutomationController::class, 'request'])->name('automation.request');
    });


    #1627256091825567|2e3620c8400527e7bef2bd1466250f67  
    #in lower webhook token EAAXHZBxVP1ZA8BRTkhEkY7f9hj0GoZAcwV9dbKExrOUsZBaW3rWEyzZA7KANPcLPZAGyNZBZAZCvBU5CgDv2UxxxDfj1VRZAd16sQFxd3ZCKWVCJToxn6t4sZC1lDGT7Bo59VOs0QuPJU2rtwVTvIN88L1lmliQtfAZAWE5zE0TEafCBuQkJ4010jaQpUffakgjZAJDSIx1zBp3ni19AZDZD