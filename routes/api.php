<?php

use App\Http\Controllers\Api\ApiKeyManagementController;
use App\Http\Controllers\Api\AppDiscoveryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutomationController;
use App\Http\Controllers\Api\BillApiController;
use App\Http\Controllers\Api\ConsumerApiController;
use App\Http\Controllers\Api\MobileSyncController;
use App\Http\Controllers\Api\MruApiController;
use App\Http\Controllers\Api\OpenApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes (v1)
|--------------------------------------------------------------------------
|
| Dual Authentication supported:
| - 'X-API-Key: nbp_live_...' (Ideal for Python ADB automation & scripts)
| - 'Authorization: Bearer <key_or_token>' (Ideal for Flutter Mobile & external clients)
|
*/

Route::prefix('v1')->middleware(['api.analytics', 'api.feature'])->group(function () {

    // Mobile App Dynamic Endpoint Discovery & Server URL Handshake
    Route::get('/app/config', [AppDiscoveryController::class, 'config'])->name('api.app.config');

    // Machine-readable OpenAPI 3.0 specification for AI Agents and Swagger
    Route::get('/openapi.json', [OpenApiController::class, 'schema'])
        ->middleware(['throttle:api.openapi', 'api.feature:docs'])
        ->name('api.openapi');

    // Authentication: Mobile / Token Login (Brute-force protected)
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api.login');

    // Authenticated Endpoints (API Key or Bearer Token required + High-capacity rate limit)
    Route::middleware(['auth.apikey', 'throttle:api.general'])->group(function () {

        // User & Session Profile
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // API Key Management (Provision, List, Revoke)
        Route::get('/api-keys', [ApiKeyManagementController::class, 'index']);
        Route::post('/api-keys', [ApiKeyManagementController::class, 'store']);
        Route::delete('/api-keys/{id}', [ApiKeyManagementController::class, 'destroy']);

        // MRU Workspaces & Billing Cycles
        Route::get('/mrus', [MruApiController::class, 'index']);
        Route::get('/mrus/cycles', [MruApiController::class, 'cycles']);
        Route::get('/mrus/{mruId}/cycles', [MruApiController::class, 'cycles']);

        // 1. Core Bills & Human Review Ledger (PRD / TRD Specification)
        Route::get('/bills', [BillApiController::class, 'index']);
        Route::patch('/bills/review', [BillApiController::class, 'review'])->middleware('throttle:api.review');
        Route::post('/bills/batch-sync', [BillApiController::class, 'batchSync'])
            ->middleware(['throttle:api.batch', 'api.feature:batch_sync']);
        Route::post('/bills/quick-pull', [BillApiController::class, 'quickPull']);

        // 2. Python ADB Automation Tool Endpoints (V:\reading-meter-app)
        Route::middleware('api.feature:automation')->group(function () {
            Route::get('/automation/queue', [AutomationController::class, 'getQueue']);
            Route::post('/automation/update-status', [AutomationController::class, 'updateStatus'])->middleware('throttle:api.review');
        });

        // 3. Flutter Mobile Offline Synchronization (Sync-In & Sync-Out)
        Route::middleware('api.feature:mobile_sync')->group(function () {
            Route::get('/sync/mrus/{mruId}/download', [MobileSyncController::class, 'downloadMruPayload']);
            Route::post('/sync/readings/batch', [MobileSyncController::class, 'uploadBatchReadings'])->middleware('throttle:api.batch');
        });

        // 4. Consumer Search, Reading History Ledger & Single Updates
        Route::get('/consumers', [ConsumerApiController::class, 'index']);
        Route::get('/consumers/{ca}', [ConsumerApiController::class, 'show']);
        Route::get('/consumers/{ca}/history', [ConsumerApiController::class, 'history']);
        Route::post('/consumers/reading', [ConsumerApiController::class, 'updateReading'])
            ->middleware('api.feature:consumer_updates');
    });
});
