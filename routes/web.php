<?php

use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\SuperAdmin;
use App\Http\Controllers\Web\Tenant;
use App\Http\Controllers\Web\Tenant\ScreenshotController;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════
// PUBLIC — Auth routes (base domain: saas-crm.test)
// ══════════════════════════════════════════════════════════════════

Route::get('/', fn() => view('welcome'))->name('home');

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
// SUPER ADMIN (base domain: saas-crm.test/superadmin)
// ══════════════════════════════════════════════════════════════════

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {

        Route::get('/dashboard', [SuperAdmin\DashboardController::class, 'index'])
            ->name('dashboard');

        // Tenant management
        // Route::resource('tenants', SuperAdmin\TenantController::class);

        // Plan management
        // Route::resource('plans', SuperAdmin\PlanController::class);
    });

// ══════════════════════════════════════════════════════════════════
// TENANT ROUTES (subdomain: {tenant}.saas-crm.test)
//
// Route::domain() use karte hain — {tenant} parameter automatically
// aata hai lekin Lead/Contact model injection ke liye
// hume explicit binding use karni padegi
// ══════════════════════════════════════════════════════════════════

// Route::domain('{tenant}.' . config('app.base_domain', 'saas-crm.test'))
Route::middleware(['tenant', 'auth', 'subscription'])
    ->name('tenant.')
    ->group(function () {

        // ── Dashboard ─────────────────────────────────────────────
        Route::get('/dashboard', [Tenant\DashboardController::class, 'index'])
            ->name('dashboard');

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
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::patch('/update-stage/{id}', 'updateStage')->name('update_stage');
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
                Route::post('/screenshots/upload', [ScreenshotController::class, 'upload'])
                    ->name('upload');

                // Admin: view & delete
                Route::get('/attendance/{attendance}/screenshots', [ScreenshotController::class, 'show'])
                    ->name('show');

                Route::delete('/screenshots/{screenshot}', [ScreenshotController::class, 'destroy'])
                    ->name('destroy');
            });
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
        });
    });
