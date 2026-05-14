<?php

use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\SuperAdmin;
use App\Http\Controllers\Web\Tenant;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════
// PUBLIC ROUTES
// ══════════════════════════════════════════════════════════════════

Route::get('/', fn() => view('welcome'))->name('home');

// ── Guest only (redirect to dashboard if already logged in) ───────
Route::middleware('guest')->group(function () {

    // Login
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    // Register
    Route::get('/register',  [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    // Forgot password
    Route::get('/forgot-password',  [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    // Reset password
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password',        [ResetPasswordController::class, 'store'])->name('password.update');
});

// Logout
Route::post('/logout', [LoginController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');




// Subscription expired page
// Route::get('/subscription/expired', fn () => view('errors.subscription-expired'))
//     ->name('subscription.expired')
//     ->middleware('auth');

// ══════════════════════════════════════════════════════════════════
// TENANT ROUTES
// (subdomain identify + auth + subscription check)
// ══════════════════════════════════════════════════════════════════

Route::domain('{tenant}.saas-crm.test')->middleware(['tenant', 'auth', 'subscription'])->name('tenant.')->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────
    Route::get('/dashboard', [Tenant\DashboardController::class, 'index'])
        ->name('dashboard');


    // ── Leads ─────────────────────────────────────────────────────



    Route::resource('leads', Tenant\LeadController::class);

    Route::prefix('leads')->name('leads.')->group(function () {
        Route::post('{lead}/assign',  [Tenant\LeadController::class, 'assign'])->name('assign');
        Route::post('{lead}/convert', [Tenant\LeadController::class, 'convert'])->name('convert');
        Route::post('{lead}/status',  [Tenant\LeadController::class, 'updateStatus'])->name('status');
    });

    // ── Contacts ──────────────────────────────────────────────────
    // Route::resource('contacts', Tenant\ContactController::class);

    // ── Deals ─────────────────────────────────────────────────────
    // Route::resource('deals', Tenant\DealController::class);

    Route::prefix('deals')->name('deals.')->group(function () {
        // Route::put('{deal}/stage',  [Tenant\DealController::class, 'updateStage'])->name('stage');
        // Route::post('{deal}/won',   [Tenant\DealController::class, 'markWon'])->name('won');
        // Route::post('{deal}/lost',  [Tenant\DealController::class, 'markLost'])->name('lost');
    });

    // ── Follow-ups ────────────────────────────────────────────────
    Route::resource('followups', Tenant\FollowupController::class);

    Route::prefix('followups')->name('followups.')->group(function () {
        Route::post('{followup}/done',   [Tenant\FollowupController::class, 'markDone'])->name('done');
        // Route::post('{followup}/missed', [Tenant\FollowupController::class, 'markMissed'])->name('missed');
    });

    // ── Tasks ─────────────────────────────────────────────────────
    // Route::resource('tasks', Tenant\TaskController::class);

    Route::prefix('tasks')->name('tasks.')->group(function () {
        // Route::post('{task}/complete', [Tenant\TaskController::class, 'markComplete'])->name('complete');
        // Route::post('{task}/reopen',   [Tenant\TaskController::class, 'reopen'])->name('reopen');
    });

    // ── Quotations ────────────────────────────────────────────────
    // Route::resource('quotations', Tenant\QuotationController::class);

    Route::prefix('quotations')->name('quotations.')->group(function () {
        // Route::get('{quotation}/pdf',         [Tenant\QuotationController::class, 'pdf'])->name('pdf');
        // Route::post('{quotation}/send',       [Tenant\QuotationController::class, 'send'])->name('send');
        // Route::post('{quotation}/convert',    [Tenant\QuotationController::class, 'convertToInvoice'])->name('convert');
        // Route::put('{quotation}/status',      [Tenant\QuotationController::class, 'updateStatus'])->name('status');
    });

    // ── Invoices ──────────────────────────────────────────────────
    // Route::resource('invoices', Tenant\InvoiceController::class);

    Route::prefix('invoices')->name('invoices.')->group(function () {
        // Route::get('{invoice}/pdf',       [Tenant\InvoiceController::class, 'pdf'])->name('pdf');
        // Route::post('{invoice}/send',     [Tenant\InvoiceController::class, 'send'])->name('send');
        // Route::post('{invoice}/mark-paid',[Tenant\InvoiceController::class, 'markPaid'])->name('mark-paid');
        // Route::post('{invoice}/remind',   [Tenant\InvoiceController::class, 'remind'])->name('remind');
    });

    // ── Staff Management ──────────────────────────────────────────
    // Route::resource('staff', Tenant\StaffController::class);

    Route::prefix('staff')->name('staff.')->group(function () {
        // Route::post('{staff}/activate',   [Tenant\StaffController::class, 'activate'])->name('activate');
        // Route::post('{staff}/deactivate', [Tenant\StaffController::class, 'deactivate'])->name('deactivate');
    });

    // ── Departments ───────────────────────────────────────────────
    // Route::resource('departments', Tenant\DepartmentController::class)
    //     ->except(['show']);

    // ── Attendance ────────────────────────────────────────────────
    // Route::resource('attendance', Tenant\AttendanceController::class)
    //     ->except(['destroy']);

    Route::prefix('attendance')->name('attendance.')->group(function () {
        // Route::post('clock-in',  [Tenant\AttendanceController::class, 'clockIn'])->name('clock-in');
        // Route::post('clock-out', [Tenant\AttendanceController::class, 'clockOut'])->name('clock-out');
        // Route::get('my',         [Tenant\AttendanceController::class, 'my'])->name('my');
        // Route::get('report',     [Tenant\AttendanceController::class, 'report'])->name('report');
    });

    // ── WhatsApp ──────────────────────────────────────────────────
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        // Route::get('/',                  [Tenant\WhatsappController::class, 'index'])->name('index');
        // Route::get('templates',          [Tenant\WhatsappController::class, 'templates'])->name('templates');
        // Route::post('templates',         [Tenant\WhatsappController::class, 'storeTemplate'])->name('templates.store');
        // Route::put('templates/{id}',     [Tenant\WhatsappController::class, 'updateTemplate'])->name('templates.update');
        // Route::delete('templates/{id}',  [Tenant\WhatsappController::class, 'deleteTemplate'])->name('templates.delete');
        // Route::get('send',               [Tenant\WhatsappController::class, 'sendForm'])->name('send');
        // Route::post('send',              [Tenant\WhatsappController::class, 'send'])->name('send.store');
        // Route::post('send-bulk',         [Tenant\WhatsappController::class, 'sendBulk'])->name('send.bulk');
        // Route::get('logs',               [Tenant\WhatsappController::class, 'logs'])->name('logs');
    });

    // ── Email ─────────────────────────────────────────────────────
    Route::prefix('email')->name('email.')->group(function () {
        // Route::get('/',                  [Tenant\EmailController::class, 'index'])->name('index');
        // Route::get('templates',          [Tenant\EmailController::class, 'templates'])->name('templates');
        // Route::post('templates',         [Tenant\EmailController::class, 'storeTemplate'])->name('templates.store');
        // Route::put('templates/{id}',     [Tenant\EmailController::class, 'updateTemplate'])->name('templates.update');
        // Route::delete('templates/{id}',  [Tenant\EmailController::class, 'deleteTemplate'])->name('templates.delete');
        // Route::get('send',               [Tenant\EmailController::class, 'sendForm'])->name('send');
        // Route::post('send',              [Tenant\EmailController::class, 'send'])->name('send.store');
        // Route::post('send-bulk',         [Tenant\EmailController::class, 'sendBulk'])->name('send.bulk');
        // Route::get('logs',               [Tenant\EmailController::class, 'logs'])->name('logs');
    });

    // ── Reports ───────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        // Route::get('/',          [Tenant\ReportController::class, 'index'])->name('index');
        // Route::get('leads',      [Tenant\ReportController::class, 'leads'])->name('leads');
        // Route::get('deals',      [Tenant\ReportController::class, 'deals'])->name('deals');
        // Route::get('revenue',    [Tenant\ReportController::class, 'revenue'])->name('revenue');
        // Route::get('staff',      [Tenant\ReportController::class, 'staff'])->name('staff');
        // Route::get('attendance', [Tenant\ReportController::class, 'attendance'])->name('attendance');
    });

    // Alias for sidebar link
    // Route::get('/reports', [Tenant\ReportController::class, 'index'])->name('reports');

    // ── Subscription ──────────────────────────────────────────────
    Route::prefix('subscription')->name('subscription.')->group(function () {
        // Route::get('/',         [Tenant\SubscriptionController::class, 'current'])->name('current');
        // Route::get('plans',     [Tenant\SubscriptionController::class, 'plans'])->name('plans');
        // Route::post('upgrade',  [Tenant\SubscriptionController::class, 'upgrade'])->name('upgrade');
        // Route::post('cancel',   [Tenant\SubscriptionController::class, 'cancel'])->name('cancel');
    });

    // ── Settings ──────────────────────────────────────────────────
    Route::prefix('settings')->name('settings.')->group(function () {
        //     Route::get('/',          [Tenant\SettingsController::class, 'index'])->name('index');
        //     Route::put('company',    [Tenant\SettingsController::class, 'updateCompany'])->name('company');
        //     Route::put('profile',    [Tenant\SettingsController::class, 'updateProfile'])->name('profile');
        //     Route::put('password',   [Tenant\SettingsController::class, 'updatePassword'])->name('password');
        //     Route::post('logo',      [Tenant\SettingsController::class, 'uploadLogo'])->name('logo');
        //     Route::post('avatar',    [Tenant\SettingsController::class, 'uploadAvatar'])->name('avatar');
        //     Route::get('users',      [Tenant\SettingsController::class, 'users'])->name('users');
        //     Route::post('users',     [Tenant\SettingsController::class, 'storeUser'])->name('users.store');
        //     Route::put('users/{user}',    [Tenant\SettingsController::class, 'updateUser'])->name('users.update');
        //     Route::delete('users/{user}', [Tenant\SettingsController::class, 'destroyUser'])->name('users.destroy');
    });

    // Alias for sidebar link
    // Route::get('/settings', [Tenant\SettingsController::class, 'index'])->name('settings');

    // ── Razorpay webhook ──────────────────────────────────────────
    // Route::post('/webhook/razorpay', [Tenant\SubscriptionController::class, 'webhook'])
    //     ->name('webhook.razorpay')
    //     ->withoutMiddleware(['subscription']);

});


// ══════════════════════════════════════════════════════════════════
// SUPER ADMIN ROUTES
// ══════════════════════════════════════════════════════════════════

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [SuperAdmin\DashboardController::class, 'index'])
            ->name('dashboard');


        // ── Tenant management ─────────────────────────────────────────
        // Route::resource('tenants', SuperAdmin\TenantController::class);

        Route::prefix('tenants')->name('tenants.')->group(function () {
            // Route::post('{tenant}/suspend',   [SuperAdmin\TenantController::class, 'suspend'])->name('suspend');
            // Route::post('{tenant}/activate',  [SuperAdmin\TenantController::class, 'activate'])->name('activate');
            // Route::post('{tenant}/impersonate',[SuperAdmin\TenantController::class, 'impersonate'])->name('impersonate');
        });

        // ── Plan management ───────────────────────────────────────────
        // Route::resource('plans', SuperAdmin\PlanController::class);

        Route::prefix('plans')->name('plans.')->group(function () {
            // Route::post('{plan}/toggle', [SuperAdmin\PlanController::class, 'toggle'])->name('toggle');
        });

        // ── Subscription management ───────────────────────────────────
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            // Route::get('/',                          [SuperAdmin\SubscriptionController::class, 'index'])->name('index');
            // Route::post('{subscription}/extend',     [SuperAdmin\SubscriptionController::class, 'extend'])->name('extend');
            // Route::post('{subscription}/cancel',     [SuperAdmin\SubscriptionController::class, 'cancel'])->name('cancel');
            // Route::post('{subscription}/activate',   [SuperAdmin\SubscriptionController::class, 'activate'])->name('activate');
        });

        // ── Reports ───────────────────────────────────────────────────
        Route::prefix('reports')->name('reports.')->group(function () {
            // Route::get('/',         [SuperAdmin\ReportController::class, 'index'])->name('index');
            // Route::get('revenue',   [SuperAdmin\ReportController::class, 'revenue'])->name('revenue');
            // Route::get('tenants',   [SuperAdmin\ReportController::class, 'tenants'])->name('tenants');
            // Route::get('signups',   [SuperAdmin\ReportController::class, 'signups'])->name('signups');
        });

        // ── Settings ──────────────────────────────────────────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            // Route::get('/',       [SuperAdmin\SettingsController::class, 'index'])->name('index');
            // Route::put('general', [SuperAdmin\SettingsController::class, 'updateGeneral'])->name('general');
            // Route::put('smtp',    [SuperAdmin\SettingsController::class, 'updateSmtp'])->name('smtp');
            // Route::put('payment', [SuperAdmin\SettingsController::class, 'updatePayment'])->name('payment');
        });
    });
