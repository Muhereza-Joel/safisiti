<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageSyncController;
use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VersionCheckController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('sync/pull', [SyncController::class, 'syncPull']);
        Route::post('sync/push', [SyncController::class, 'syncPush']);

        // Get image sync status
        Route::get('images/sync', [ImageSyncController::class, 'getSyncStatus']);
        // Upload image
        Route::post('images/upload', [ImageSyncController::class, 'uploadImage']);
        // Get image by ID
        Route::get('images/{id}', [ImageSyncController::class, 'getImage']);
        // Get images by associated entity
        Route::get('entities/{entityType}/{entityId}/images', [ImageSyncController::class, 'getEntityImages']);
        // Clean up orphaned images
        Route::post('images/cleanup', [ImageSyncController::class, 'cleanupOrphanedImages']);

        // Get inspectors specifically
        Route::get('/collection-agents', [UserController::class, 'getCollectionAgents']);

        // Get service providers specifically  
        Route::get('/service-providers', [UserController::class, 'getServiceProviders']);

        Route::get('/service-providers/{uuid}', [UserController::class, 'getUserByUuid']);
        Route::get('/collection-agents/{uuid}', [UserController::class, 'getUserByUuid']);


        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/roles', [AuthController::class, 'getRoles']);
    Route::get('/server-time', [ServerTimeController::class, 'index']);
    Route::match(['get', 'head'], '/health', function () {
        return response()->noContent(200);
    });
    Route::get('/version-check', [VersionCheckController::class, 'check']);
});
