<?php

use App\Domain\System\Http\Controllers\HealthController;
use App\Domain\System\Http\Controllers\SystemStatusController;
use Illuminate\Support\Facades\Route;

// System: liveness + full status of Core/Database/local server/Internet.
// Future modules (Tables, Orders, Menu, ...) register their own route files
// here as they land — see docs/architecture.md "Module structure".
Route::get('/health', HealthController::class);
Route::get('/system/status', SystemStatusController::class);
