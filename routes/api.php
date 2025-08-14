<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('sync/pull', [SyncController::class, 'syncPull']);
        Route::get('sync/preferences', [SyncController::class, 'syncPreferences']);

        Route::post('sync/push', [SyncController::class, 'syncPush']);
        Route::post('sync/contacts', [SyncController::class, 'syncContacts']);
        Route::post('sync/preferences', [SyncController::class, 'syncPreferences']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/roles', [AuthController::class, 'getRoles']);
    Route::get('sync/users', [SyncController::class, 'pullUsers']);
    Route::post('sync/users', [SyncController::class, 'pushUsers']);
});
