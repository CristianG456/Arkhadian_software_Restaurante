<?php

use App\Domain\Areas\Http\Controllers\AreaController;
use App\Domain\System\Http\Controllers\HealthController;
use App\Domain\System\Http\Controllers\SystemStatusController;
use App\Domain\Tables\Http\Controllers\RestaurantTableController;
use Illuminate\Support\Facades\Route;

// System: liveness + full status of Core/Database/local server/Internet.
// Future modules (Tables, Orders, Menu, ...) register their own route files
// here as they land — see docs/architecture.md "Module structure".
Route::get('/health', HealthController::class);
Route::get('/system/status', SystemStatusController::class);

Route::prefix('areas')->group(function (): void {
    Route::get('/', [AreaController::class, 'index']);
    Route::post('/', [AreaController::class, 'store']);
    Route::post('/reorder', [AreaController::class, 'reorder']);
    Route::get('/{area}', [AreaController::class, 'show']);
    Route::put('/{area}', [AreaController::class, 'update']);
    Route::delete('/{area}', [AreaController::class, 'destroy']);
    Route::get('/{area}/tables', [RestaurantTableController::class, 'index']);
    Route::post('/{area}/tables', [RestaurantTableController::class, 'store']);
    Route::post('/{area}/tables/bulk', [RestaurantTableController::class, 'bulkStore']);
    Route::post('/{area}/tables/reorder', [RestaurantTableController::class, 'reorder']);
});

Route::put('/tables/{table}', [RestaurantTableController::class, 'update']);
Route::delete('/tables/{table}', [RestaurantTableController::class, 'destroy']);
