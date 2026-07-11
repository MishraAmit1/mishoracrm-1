<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\Tenant\ContactController as ApiContactController;
use App\Http\Controllers\Api\Tenant\LeadController    as ApiLeadController;
use App\Http\Controllers\Api\Tenant\DealController    as ApiDealController;
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
        Route::get('/leads',             [ApiLeadController::class, 'index']);
        Route::post('/leads',            [ApiLeadController::class, 'store']);
        Route::get('/leads/{id}',        [ApiLeadController::class, 'show']);
        Route::put('/leads/{id}',        [ApiLeadController::class, 'update']);
        Route::delete('/leads/{id}',     [ApiLeadController::class, 'destroy']);

        // Deals
        Route::get('/deals',             [ApiDealController::class, 'index']);
        Route::post('/deals',            [ApiDealController::class, 'store']);
        Route::get('/deals/{id}',        [ApiDealController::class, 'show']);
        Route::put('/deals/{id}',        [ApiDealController::class, 'update']);
        Route::delete('/deals/{id}',     [ApiDealController::class, 'destroy']);
    });
  Route::middleware('auth')->post('/device-token', [DeviceTokenController::class, 'store']);
});
