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
use App\Http\Controllers\Web\PricingController;
use Illuminate\Support\Facades\Route;
// ══════════════════════════════════════════════════════════════════
// PUBLIC — Auth routes (base domain: saas-crm.test)
// ══════════════════════════════════════════════════════════════════

Route::get('/', fn() => view('welcome'))->name('home');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('/privacy-policy', fn() => view('legal.privacy-policy'))->name('privacy-policy');
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
// PUBLIC — Customer-facing quotation accept/reject (token-guarded, no auth)
// ══════════════════════════════════════════════════════════════════

Route::prefix('/quote')->name('public.quotations.')->controller(\App\Http\Controllers\Public\QuotationController::class)->group(function () {
    Route::get('/{token}', 'show')->name('show');
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/{token}/accept', 'accept')->name('accept');
        Route::post('/{token}/reject', 'reject')->name('reject');
    });
});

// ══════════════════════════════════════════════════════════════════
// PUBLIC — Customer-facing appointment booking (token-guarded, no auth)
// ══════════════════════════════════════════════════════════════════

Route::prefix('/book')->name('public.booking.')->controller(\App\Http\Controllers\Public\AppointmentController::class)->group(function () {
    Route::get('/appointment/{token}', 'showAppointment')->name('appointment');
    Route::get('/{token}', 'show')->name('show');
    Route::get('/{token}/slots', 'slots')->name('slots');
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/{token}/store', 'store')->name('store');
        Route::post('/appointment/{token}/cancel', 'cancelByCustomer')->name('cancel');
    });
});

// ══════════════════════════════════════════════════════════════════
// PUBLIC — Customer-facing support ticket submission (token-guarded, no auth)
// ══════════════════════════════════════════════════════════════════

Route::prefix('/support')->name('public.support.')->controller(\App\Http\Controllers\Public\TicketController::class)->group(function () {
    Route::get('/ticket/{token}', 'showTicket')->name('ticket');
    Route::get('/{token}', 'show')->name('show');
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/{token}/store', 'store')->name('store');
        Route::post('/ticket/{token}/reply', 'replyAsCustomer')->name('reply');
    });
});

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
            Route::post('/{tenant}/toggle-manufacturing', 'toggleManufacturing')->name('toggle-manufacturing');
            Route::post('/{tenant}/clear-manufacturing-override', 'clearManufacturingOverride')->name('clear-manufacturing-override');
            Route::post('/{tenant}/toggle-service', 'toggleService')->name('toggle-service');
            Route::post('/{tenant}/clear-service-override', 'clearServiceOverride')->name('clear-service-override');
            Route::post('/{tenant}/modules/{module}/toggle', 'toggleModule')->name('toggle-module');
            Route::post('/{tenant}/modules/{module}/clear', 'clearModuleOverride')->name('clear-module-override');
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
            Route::post('/toggle-monthly-billing', 'toggleMonthlyBilling')->name('toggle-monthly-billing');
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

        // Tenant Admin role permissions (only Super Admin can change this — it's one shared role across all tenants)
        Route::prefix('roles')->name('roles.')->controller(SuperAdmin\RoleController::class)->group(function () {
            Route::get('/tenant-admin/edit', 'editTenantAdmin')->name('tenant-admin.edit');
            Route::put('/tenant-admin',      'updateTenantAdmin')->name('tenant-admin.update');
        });

        // Permissions master list (no seeder edits needed for new modules)
        Route::prefix('permissions')->name('permissions.')->controller(SuperAdmin\PermissionController::class)->group(function () {
            Route::get('/',                  'index')->name('index');
            Route::get('/create',            'create')->name('create');
            Route::post('/',                 'store')->name('store');
            Route::get('/{permission}/edit', 'edit')->name('edit');
            Route::put('/{permission}',      'update')->name('update');
            Route::delete('/{permission}',   'destroy')->name('destroy');
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

        // ── Global Search ─────────────────────────────────────────
        // Path must be exactly /search — the topbar JS hardcodes this URL.
        Route::get('/search', [Tenant\SearchController::class, 'index'])->name('search');

        // ── Calendar ───────────────────────────────────────────────
        Route::get('/calendar',              [Tenant\CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events',        [Tenant\CalendarController::class, 'events'])->name('calendar.events');
        Route::post('/calendar/reschedule',   [Tenant\CalendarController::class, 'reschedule'])->name('calendar.reschedule');
        Route::post('/calendar/quick-create', [Tenant\CalendarController::class, 'quickCreate'])->name('calendar.quick-create');

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

        // Import / Export / Duplicates — static segments must be registered
        // before /leads/{id} below, which has no numeric constraint.
        Route::middleware('permission:leads.import')->group(function () {
            Route::get('/leads/import',          [Tenant\LeadImportController::class, 'show'])->name('leads.import');
            Route::post('/leads/import/preview', [Tenant\LeadImportController::class, 'preview'])->name('leads.import.preview');
            Route::post('/leads/import/confirm', [Tenant\LeadImportController::class, 'confirm'])->name('leads.import.confirm');
            Route::get('/leads/import/template', [Tenant\LeadImportController::class, 'template'])->name('leads.import.template');
        });
        Route::get('/leads/export', [Tenant\LeadImportController::class, 'export'])
            ->name('leads.export')->middleware('permission:leads.export');

        Route::middleware('permission:leads.merge')->group(function () {
            Route::get('/leads/duplicates',        [Tenant\DuplicateController::class, 'leadsIndex'])->name('leads.duplicates');
            Route::post('/leads/duplicates/merge', [Tenant\DuplicateController::class, 'mergeLeads'])->name('leads.duplicates.merge');
        });

        Route::post('/leads/check-duplicate', [Tenant\LeadController::class, 'checkDuplicate'])->name('leads.check-duplicate');

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
        Route::post('/leads/bulk-status',        [Tenant\LeadController::class, 'bulkUpdateStatus'])->name('leads.bulk-status');
        Route::post('/leads/bulk-destroy',       [Tenant\LeadController::class, 'bulkDestroy'])->name('leads.bulk-destroy');
        Route::post('/leads/bulk-assign',        [Tenant\LeadController::class, 'bulkAssign'])->name('leads.bulk-assign');
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
            });

            // Import / Export / Duplicates — static segments, must precede /{id} below.
            Route::middleware('permission:contacts.import')->controller(Tenant\ContactImportController::class)->group(function () {
                Route::get('/import',          'show')->name('import');
                Route::post('/import/preview', 'preview')->name('import.preview');
                Route::post('/import/confirm', 'confirm')->name('import.confirm');
                Route::get('/import/template', 'template')->name('import.template');
            });
            Route::get('/export', [Tenant\ContactImportController::class, 'export'])
                ->name('export')->middleware('permission:contacts.export');

            Route::middleware('permission:contacts.merge')->controller(Tenant\DuplicateController::class)->group(function () {
                Route::get('/duplicates',        'contactsIndex')->name('duplicates');
                Route::post('/duplicates/merge', 'mergeContacts')->name('duplicates.merge');
            });

            Route::post('/check-duplicate', [Tenant\ContactController::class, 'checkDuplicate'])->name('check-duplicate');

            // Employee attachment delete — static "employees" segment, must precede /{id} below.
            Route::delete('/employees/{employee}/attachments/{attachment}', [Tenant\ContactController::class, 'destroyEmployeeAttachment'])->name('employees.attachments.destroy');

            Route::controller(Tenant\ContactController::class)->group(function () {
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::get('/{id}/report', 'customerReport')->name('report');
                Route::delete('/{id}/attachments/{attachment}', 'destroyAttachment')->name('attachments.destroy');
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
                Route::get('/export', 'export')->name('export')->middleware('permission:deals.export');
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
                Route::get('/export', 'export')->name('export')->middleware('permission:quotations.export');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/status', 'updateStatus')->name('update_status');
                Route::get('/{id}/pdf', 'pdf')->name('pdf');
                Route::post('/{id}/send', 'send')->name('send');
                Route::post('/{id}/convert', 'convertToInvoice')->name('convert');
                Route::post('/{id}/new-version', 'newVersion')->name('new_version');
                Route::get('/{id}/data', 'quotationData')->name('data');
            });
        });

        // Quotation Terms & Conditions templates (reusable snippets)
        Route::prefix('/quotation-terms-templates')->name('quotation-terms-templates.')
            ->controller(Tenant\QuotationTermsTemplateController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::middleware('permission:quotations.edit')->group(function () {
                    Route::post('/', 'store')->name('store');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
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
                Route::post('/{id}/send', 'send')->name('send');
                Route::post('/{id}/record-payment', 'recordPayment')->name('record_payment');
            });
        });

        // ── Invoice PDF Style (tenant_admin only) ───────────────────
        Route::prefix('invoice-pdf-style')->name('invoice-pdf-style.')->middleware(['role:tenant_admin'])
            ->controller(Tenant\InvoicePdfSettingController::class)->group(function () {
                Route::get('/',  'index')->name('index');
                Route::post('/', 'store')->name('store');
            });

        // Products / Item Catalog routes
        Route::prefix('/products')->name('products.')->group(function () {
            Route::controller(Tenant\ProductController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/search', 'search')->name('search');
                Route::get('/low-stock', 'lowStock')->name('low-stock');
                Route::get('/{id}/batches', 'batches')->name('batches')->middleware('module:manufacturing');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        });

        // Services / Service Catalog routes — gated behind the Service module toggle
        Route::prefix('/services')->name('services.')->middleware('module:service')->group(function () {
            Route::controller(Tenant\ServiceController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        });

        // Service Subscriptions routes — customer-level tracking + expiry,
        // gated behind the same Service module toggle, plus per-action
        // permissions so tenant admins can control which staff can send
        // customer messages, cancel subscriptions, or edit templates.
        Route::prefix('/subscriptions')->name('subscriptions.')->middleware('module:subscriptions')->group(function () {
            Route::controller(Tenant\ServiceSubscriptionController::class)->group(function () {
                Route::middleware('permission:subscriptions.view')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/history', 'history')->name('history');
                    Route::get('/contact-invoices/{contactId}', 'contactInvoices')->name('contact-invoices');
                });
                Route::middleware('permission:subscriptions.create')->group(function () {
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                });
                Route::middleware('permission:subscriptions.manage_templates')->group(function () {
                    Route::post('/preferences', 'updatePreferences')->name('preferences');
                    Route::post('/preferences/test-email', 'sendTestEmail')->name('preferences.test-email');
                });
                Route::middleware('permission:subscriptions.edit')->group(function () {
                    Route::get('/{id}/edit', 'edit')->name('edit');
                    Route::put('/{id}', 'update')->name('update');
                });
                Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:subscriptions.delete');
                Route::post('/{id}/cancel', 'cancel')->name('cancel')->middleware('permission:subscriptions.cancel');
                Route::post('/bulk-renew', 'bulkRenew')->name('bulk-renew')->middleware('permission:subscriptions.renew');
                Route::post('/bulk-cancel', 'bulkCancel')->name('bulk-cancel')->middleware('permission:subscriptions.cancel');
                Route::post('/{id}/mark-used', 'markUsed')->name('mark-used')->middleware('permission:subscriptions.edit');
                Route::post('/{id}/renew', 'renew')->name('renew')->middleware('permission:subscriptions.renew');
                Route::post('/{id}/send-reminder', 'sendReminder')->name('send-reminder')->middleware('permission:subscriptions.send_reminder');
            });
        });

        // Appointments / Booking routes — gated behind the Service module toggle
        Route::prefix('/appointments')->name('appointments.')->middleware('module:appointments')->group(function () {
            Route::controller(Tenant\AppointmentController::class)->group(function () {
                Route::middleware('permission:appointments.view')->group(function () {
                    Route::get('/', 'index')->name('index');
                });
                Route::middleware('permission:appointments.create')->group(function () {
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/slots', 'slots')->name('slots');
                });
                Route::middleware('permission:appointments.manage_settings')->group(function () {
                    Route::get('/settings', 'settings')->name('settings');
                    Route::post('/settings', 'updateSettings')->name('settings.update');
                });
                Route::post('/{id}/status', 'updateStatus')->name('status')->middleware('permission:appointments.edit');
                Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:appointments.cancel');
            });
        });

        // Time Tracking routes — gated behind the Service module toggle
        Route::prefix('/time-entries')->name('time-entries.')->middleware('module:time_tracking')->group(function () {
            Route::controller(Tenant\TimeEntryController::class)->group(function () {
                Route::middleware('permission:time_entries.view')->group(function () {
                    Route::get('/', 'index')->name('index');
                });
                Route::middleware('permission:time_entries.create')->group(function () {
                    Route::post('/start', 'start')->name('start');
                    Route::post('/{id}/stop', 'stop')->name('stop');
                    Route::post('/', 'store')->name('store');
                });
                Route::middleware('permission:time_entries.edit')->group(function () {
                    Route::put('/{id}', 'update')->name('update');
                });
                Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:time_entries.delete');
                Route::post('/convert-to-invoice', 'convertToInvoice')->name('convert-to-invoice')->middleware('permission:time_entries.convert_to_invoice');
            });
        });

        // Tickets / Helpdesk routes — gated behind its own module toggle
        // (relevant to every tenant, not just service-based ones).
        Route::prefix('/tickets')->name('tickets.')->middleware('module:tickets')->group(function () {
            Route::controller(Tenant\TicketController::class)->group(function () {
                Route::middleware('permission:tickets.view_all|tickets.view_own')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{id}', 'show')->name('show');
                });
                Route::middleware('permission:tickets.create')->group(function () {
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                });
                Route::middleware('permission:tickets.reply')->group(function () {
                    Route::post('/{id}/reply', 'reply')->name('reply');
                    Route::post('/{id}/attachments', 'storeAttachment')->name('attachments.store');
                });
                Route::middleware('permission:tickets.edit')->group(function () {
                    Route::post('/{id}/status', 'updateStatus')->name('status');
                    Route::post('/{id}/priority', 'updatePriority')->name('priority');
                    Route::post('/{id}/assign', 'assign')->name('assign');
                    Route::post('/preferences', 'updatePreferences')->name('preferences');
                    Route::post('/preferences/test-email', 'sendTestEmail')->name('preferences.test-email');
                });
                Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:tickets.delete');
                Route::delete('/{ticketId}/attachments/{attachmentId}', 'destroyAttachment')->name('attachments.destroy')->middleware('permission:tickets.edit');
            });
        });

        // Vendors routes
        Route::prefix('/vendors')->name('vendors.')->group(function () {
            Route::controller(Tenant\VendorController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        });

        // Purchase Requests routes
        Route::prefix('/purchase-requests')->name('purchase-requests.')->group(function () {
            Route::controller(Tenant\PurchaseRequestController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/approve', 'approve')->name('approve');
                Route::post('/{id}/reject', 'reject')->name('reject');
            });
        });

        // Purchase Orders routes
        Route::prefix('/purchase-orders')->name('purchase-orders.')->group(function () {
            Route::controller(Tenant\PurchaseOrderController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export')->middleware('permission:purchase_orders.export');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/status', 'updateStatus')->name('update_status');
                Route::post('/{id}/receive', 'receive')->name('receive');
                Route::get('/{id}/pdf', 'pdf')->name('pdf');
                Route::post('/{id}/send', 'send')->name('send');
            });

            Route::prefix('/{id}/vendor-quotes')->name('vendor-quotes.')->controller(Tenant\VendorQuoteController::class)->group(function () {
                Route::post('/', 'store')->name('store');
                Route::post('/{quoteId}/select', 'select')->name('select');
                Route::delete('/{quoteId}', 'destroy')->name('destroy');
            });
        });

        // Work Orders routes — gated behind the Manufacturing module toggle
        Route::prefix('/work-orders')->name('work-orders.')->middleware('module:manufacturing')->group(function () {
            Route::controller(Tenant\WorkOrderController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/start', 'start')->name('start');
                Route::post('/{id}/complete', 'complete')->name('complete');
                Route::post('/{id}/cancel', 'cancel')->name('cancel');
                Route::post('/{id}/costs', 'updateCosts')->name('update-costs');
            });
        });

        //Tasks routes
        Route::prefix('/tasks')->name('tasks.')->group(function () {
            Route::controller(Tenant\TaskController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::post('/bulk-action', 'bulkAction')->name('bulk_action');
                Route::post('/saved-filters', 'storeSavedFilter')->name('saved_filters.store');
                Route::delete('/saved-filters/{id}', 'destroySavedFilter')->name('saved_filters.destroy');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::patch('/{id}/update-stage', 'updateStatus')->name('update_stage');

                Route::post('/{id}/checklist-items', 'storeChecklistItem')->name('checklist.store');
                Route::patch('/{id}/checklist-items/{item}/toggle', 'toggleChecklistItem')->name('checklist.toggle');
                Route::delete('/{id}/checklist-items/{item}', 'destroyChecklistItem')->name('checklist.destroy');

                Route::post('/{id}/comments', 'storeComment')->name('comments.store');
                Route::delete('/{id}/comments/{comment}', 'destroyComment')->name('comments.destroy');

                Route::post('/{id}/attachments', 'storeAttachment')->name('attachments.store');
                Route::delete('/{id}/attachments/{attachment}', 'destroyAttachment')->name('attachments.destroy');

                Route::post('/{id}/watchers', 'storeWatcher')->name('watchers.store');
                Route::delete('/{id}/watchers/{userId}', 'destroyWatcher')->name('watchers.destroy');

                Route::post('/{id}/dependencies', 'storeDependency')->name('dependencies.store');
                Route::delete('/{id}/dependencies/{dependsOnId}', 'destroyDependency')->name('dependencies.destroy');
            });
        });

        // Task Templates routes
        Route::prefix('/task-templates')->name('task-templates.')->group(function () {
            Route::controller(Tenant\TaskTemplateController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
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
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view_basic|reports.view_all')->group(function () {
            Route::get('/overview', [Tenant\ReportController::class, 'overview'])->name('overview');
            Route::get('/deals',    [Tenant\ReportController::class, 'deals'])->name('deals');
            Route::get('/deal-quotations', [Tenant\ReportController::class, 'dealQuotations'])->name('deal_quotations');
            Route::get('/revenue',  [Tenant\ReportController::class, 'revenue'])->name('revenue');
            Route::get('/staff',    [Tenant\ReportController::class, 'staff'])->name('staff');
            Route::get('/subscriptions', [Tenant\ReportController::class, 'subscriptions'])->name('subscriptions');
            Route::get('/appointments',  [Tenant\ReportController::class, 'appointments'])->name('appointments');
            Route::get('/tickets',       [Tenant\ReportController::class, 'tickets'])->name('tickets');
            Route::get('/time-tracking', [Tenant\ReportController::class, 'timeTracking'])->name('time-tracking');
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

        // Add new permissions (tenant_admin can add for new modules — rename/delete stays Super Admin-only
        // since tenant_admin is one shared role across all tenants)
        Route::prefix('permissions')->name('permissions.')->controller(Tenant\PermissionController::class)->middleware(['role:tenant_admin'])->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/',      'store')->name('store');
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