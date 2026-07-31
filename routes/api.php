<?php

use App\Http\Controllers\Api\OfflineSyncController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/offline/login', [OfflineSyncController::class, 'login']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/offline/logout', [OfflineSyncController::class, 'logout']);
    Route::post('/offline/sync/register-device', [OfflineSyncController::class, 'registerDevice']);
    Route::post('/offline/sync/customers', [OfflineSyncController::class, 'syncCustomers']);
    Route::post('/offline/sync/sales', [OfflineSyncController::class, 'syncSales']);
    Route::post('/offline/sync/stock-takes', [OfflineSyncController::class, 'syncStockTakes']);
    Route::get('/offline/sync/download', [OfflineSyncController::class, 'downloadData']);
    Route::get('/offline/version', [\App\Http\Controllers\Api\PwaVersionController::class, 'getVersion']);
});
