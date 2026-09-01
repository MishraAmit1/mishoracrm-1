<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Tenant\ContactController as ApiContactController;
use App\Http\Controllers\Api\Tenant\LeadController    as ApiLeadController;
use App\Http\Controllers\Api\Tenant\DealController    as ApiDealController;
use App\Http\Controllers\Api\Tenant\TaskController    as ApiTaskController;
use App\Http\Controllers\Api\Tenant\QuotationController as ApiQuotationController;
use App\Http\Controllers\Api\Tenant\LoyaltyController as ApiLoyaltyController;
use App\Http\Controllers\Api\WebhookValidationController;

Route::prefix('v1')->group(function () {

    // ── Webhook token validation (public — called by n8n) ─────────
    Route::post('/webhook/validate', [WebhookValidationController::class, 'validate']);

    // ── Auth (no key needed) ──────────────────────────────────────
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);

    // ── Sanctum-protected user routes ─────────────────────────────
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout',     [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me',          [AuthController::class, 'me']);
        Route::put('/me',          [AuthController::class, 'updateProfile']);
        Route::put('/me/password', [AuthController::class, 'changePassword']);
        Route::post('/me/avatar',  [AuthController::class, 'uploadAvatar']);
    });

    // ── Tenant API — authenticated via X-API-Key header ──────────
    // Usage: X-API-Key: crm_xxxxxxxxxxxxxxxxxxxxxxxxxx
    Route::middleware(['api.key'])->prefix('tenant')->group(function () {

        // Contacts
        Route::get('/contacts',          [ApiContactController::class, 'index']);
        Route::post('/contacts',         [ApiContactController::class, 'store']);
        Route::get('/contacts/search',   [ApiContactController::class, 'search']);
        Route::get('/contacts/{id}',     [ApiContactController::class, 'show']);
        Route::put('/contacts/{id}',     [ApiContactController::class, 'update']);
        Route::delete('/contacts/{id}',  [ApiContactController::class, 'destroy']);

        // Leads
        Route::get('/leads',                  [ApiLeadController::class, 'index']);
        Route::post('/leads',                 [ApiLeadController::class, 'store']);
        Route::get('/leads/stats',            [ApiLeadController::class, 'stats']);
        Route::get('/leads/{id}',             [ApiLeadController::class, 'show']);
        Route::put('/leads/{id}',             [ApiLeadController::class, 'update']);
        Route::delete('/leads/{id}',          [ApiLeadController::class, 'destroy']);
        Route::post('/leads/{lead}/assign',   [ApiLeadController::class, 'assign']);
        Route::post('/leads/{lead}/convert',  [ApiLeadController::class, 'convert']);
        Route::patch('/leads/{lead}/status',  [ApiLeadController::class, 'updateStatus']);

        // Deals
        Route::get('/deals',             [ApiDealController::class, 'index']);
        Route::post('/deals',            [ApiDealController::class, 'store']);
        Route::get('/deals/kanban',      [ApiDealController::class, 'kanban']);
        Route::get('/deals/stats',       [ApiDealController::class, 'stats']);
        Route::get('/deals/{id}',        [ApiDealController::class, 'show']);
        Route::put('/deals/{id}',        [ApiDealController::class, 'update']);
        Route::delete('/deals/{id}',     [ApiDealController::class, 'destroy']);
        Route::patch('/deals/{id}/stage',     [ApiDealController::class, 'updateStage']);
        Route::post('/deals/{id}/mark-won',   [ApiDealController::class, 'markWon']);
        Route::post('/deals/{id}/mark-lost',  [ApiDealController::class, 'markLost']);

        // Quotations
        Route::get('/quotations',                  [ApiQuotationController::class, 'index']);
        Route::post('/quotations',                 [ApiQuotationController::class, 'store']);
        Route::get('/quotations/{id}',              [ApiQuotationController::class, 'show']);
        Route::put('/quotations/{id}',              [ApiQuotationController::class, 'update']);
        Route::delete('/quotations/{id}',           [ApiQuotationController::class, 'destroy']);
        Route::patch('/quotations/{id}/status',     [ApiQuotationController::class, 'updateStatus']);
        Route::post('/quotations/{id}/new-version', [ApiQuotationController::class, 'newVersion']);

        // Loyalty — customer points lookup for an embedded rewards widget
        Route::get('/loyalty/lookup',       [ApiLoyaltyController::class, 'lookup']);

        // Tasks
        Route::get('/tasks',                [ApiTaskController::class, 'index']);
        Route::post('/tasks',               [ApiTaskController::class, 'store']);
        Route::get('/tasks/{id}',           [ApiTaskController::class, 'show']);
        Route::put('/tasks/{id}',           [ApiTaskController::class, 'update']);
        Route::patch('/tasks/{id}/status',  [ApiTaskController::class, 'updateStatus']);
        Route::delete('/tasks/{id}',        [ApiTaskController::class, 'destroy']);
    });
});
