<?php

use App\Domain\Areas\Http\Controllers\AreaController;
use App\Domain\Catalog\Http\Controllers\CategoryController;
use App\Domain\Catalog\Http\Controllers\ModifierController;
use App\Domain\Catalog\Http\Controllers\ProductController;
use App\Domain\Catalog\Http\Controllers\ProductImageController;
use App\Domain\Catalog\Http\Controllers\VariantController;
use App\Domain\Mobile\Http\Controllers\MobileWaiterController;
use App\Domain\Mobile\Http\Controllers\PairingAdminController;
use App\Domain\Mobile\Http\Controllers\PairingController;
use App\Domain\Mobile\Http\Controllers\WaiterAuthController;
use App\Domain\Orders\Http\Controllers\OperationController;
use App\Domain\Orders\Http\Controllers\OrderController;
use App\Domain\Orders\Http\Controllers\OrderItemController;
use App\Domain\Orders\Http\Controllers\TableSessionController;
use App\Domain\Production\Http\Controllers\PreaccountController;
use App\Domain\Production\Http\Controllers\PrinterController;
use App\Domain\Production\Http\Controllers\PrintJobController;
use App\Domain\Production\Http\Controllers\ProductionController;
use App\Domain\Production\Http\Controllers\ReprintController;
use App\Domain\Staff\Http\Controllers\StaffMemberController;
use App\Domain\Staff\Http\Controllers\WaiterOperationController;
use App\Domain\System\Http\Controllers\HealthController;
use App\Domain\System\Http\Controllers\SystemStatusController;
use App\Domain\TableQr\Http\Controllers\TableQrCredentialController;
use App\Domain\Tables\Http\Controllers\RestaurantTableController;
use Illuminate\Support\Facades\Route;

// System: liveness + full status of Core/Database/local server/Internet.
// Future modules (Tables, Orders, Menu, ...) register their own route files
// here as they land — see docs/architecture.md "Module structure".
Route::get('/health', HealthController::class);
Route::get('/system/status', SystemStatusController::class);
Route::get('/operations/tables', OperationController::class);
Route::get('/staff', [StaffMemberController::class, 'index']);
Route::get('/tables/{table}/current-session', [TableSessionController::class, 'current']);
Route::get('/table-sessions/{tableSession}', [TableSessionController::class, 'show']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::post('/device-pairing/resolve', [PairingController::class, 'resolve'])->middleware('throttle:10,1');
Route::post('/device-pairing/pair', [PairingController::class, 'pair'])->middleware('throttle:10,1');
Route::middleware('waiter.device')->group(function (): void {
    Route::post('/auth/waiter/login', [WaiterAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware('waiter.auth')->group(function (): void {
        Route::get('/auth/me', [WaiterAuthController::class, 'me']);
        Route::post('/auth/refresh', [WaiterAuthController::class, 'refresh']);
        Route::post('/auth/logout', [WaiterAuthController::class, 'logout']);
        Route::get('/waiter/me/tables', [MobileWaiterController::class, 'tables']);
        Route::get('/waiter/available-tables', [MobileWaiterController::class, 'availableTables']);
        Route::get('/waiter/catalog', [MobileWaiterController::class, 'catalog']);
        Route::get('/waiter/staff', [MobileWaiterController::class, 'staff']);
        Route::get('/waiter/table-sessions/{tableSession}', [MobileWaiterController::class, 'session']);
        Route::post('/waiter/tables/{table}/orders', [MobileWaiterController::class, 'orderForTable']);
        Route::post('/waiter/table-sessions/{tableSession}/orders', [MobileWaiterController::class, 'orderForSession']);
        Route::post('/waiter/table-sessions/{tableSession}/transfer', [MobileWaiterController::class, 'transfer']);
        Route::post('/waiter/table-sessions/{tableSession}/move', [MobileWaiterController::class, 'move']);
        Route::post('/waiter/table-sessions/{tableSession}/request-bill', [MobileWaiterController::class, 'requestBill']);
        Route::get('/waiter/table-sessions/{tableSession}/preaccount', [MobileWaiterController::class, 'preaccount']);
        Route::post('/waiter/table-sessions/{tableSession}/preaccount/print', [MobileWaiterController::class, 'printPreaccount']);
        Route::post('/waiter/orders/{order}/reprint', [MobileWaiterController::class, 'reprint']);
        Route::post('/waiter/order-items/{orderItem}/cancel', [MobileWaiterController::class, 'cancelItem']);
        Route::get('/waiter/alerts', [MobileWaiterController::class, 'alerts']);
        Route::post('/waiter/alerts/{notification}/read', [MobileWaiterController::class, 'readAlert']);
    });
});
Route::middleware('local.admin')->group(function (): void {
    Route::get('/mobile/installation', [PairingAdminController::class, 'installation']);
    Route::post('/mobile/pairings', [PairingAdminController::class, 'create']);
    Route::get('/mobile/devices', [PairingAdminController::class, 'devices']);
    Route::put('/mobile/devices/{device}', [PairingAdminController::class, 'update']);
    Route::post('/mobile/devices/{device}/revoke', [PairingAdminController::class, 'revoke']);
    Route::get('/waiter/staff/{staffMember}/tables', [WaiterOperationController::class, 'tables']);
    Route::post('/waiter/staff/{staffMember}/orders', [WaiterOperationController::class, 'order']);
    Route::post('/waiter/staff/{staffMember}/table-sessions/{tableSession}/preaccount', [WaiterOperationController::class, 'preaccount']);
    Route::get('/production/centers', [ProductionController::class, 'index']);
    Route::post('/production/centers', [ProductionController::class, 'store']);
    Route::put('/production/centers/{productionCenter}', [ProductionController::class, 'update']);
    Route::put('/production/centers/{productionCenter}/printer', [ProductionController::class, 'assign']);
    Route::get('/production/printers', [PrinterController::class, 'index']);
    Route::post('/production/printers', [PrinterController::class, 'store']);
    Route::put('/production/printers/{printer}', [PrinterController::class, 'update']);
    Route::post('/production/printers/{printer}/test', [PrinterController::class, 'test']);
    Route::get('/production/print-jobs', [PrintJobController::class, 'index']);
    Route::post('/production/print-jobs/process-next', [PrintJobController::class, 'process']);
    Route::post('/production/print-jobs/{printJob}/retry', [PrintJobController::class, 'retry']);
    Route::get('/table-sessions/{tableSession}/preaccount', [PreaccountController::class, 'preview']);
    Route::post('/table-sessions/{tableSession}/preaccount/print', [PreaccountController::class, 'print']);
    Route::post('/table-sessions/{tableSession}/request-bill', [PreaccountController::class, 'requestBill']);
    Route::post('/orders/{order}/reprint', ReprintController::class);
    Route::post('/staff', [StaffMemberController::class, 'store']);
    Route::put('/staff/{staffMember}', [StaffMemberController::class, 'update']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/order-items/{orderItem}/cancel', [OrderItemController::class, 'cancel']);
    Route::post('/table-sessions/{tableSession}/transfer-waiter', [TableSessionController::class, 'transferWaiter']);
    Route::post('/table-sessions/{tableSession}/move', [TableSessionController::class, 'move']);
});

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

Route::get('/tables/{table}', [RestaurantTableController::class, 'show']);
Route::put('/tables/{table}', [RestaurantTableController::class, 'update']);
Route::post('/tables/{table}/move', [RestaurantTableController::class, 'move']);
Route::delete('/tables/{table}', [RestaurantTableController::class, 'destroy']);
Route::get('/tables/{table}/qr', [TableQrCredentialController::class, 'show']);
Route::post('/tables/{table}/qr', [TableQrCredentialController::class, 'store']);
Route::post('/tables/{table}/qr/regenerate', [TableQrCredentialController::class, 'regenerate']);
Route::post('/tables/{table}/qr/revoke', [TableQrCredentialController::class, 'revoke']);

Route::prefix('catalog')->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/modifier-groups', [ModifierController::class, 'index']);
    Route::get('/images/{image}', [ProductImageController::class, 'show']);
    Route::middleware('local.admin')->group(function (): void {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
        Route::post('/categories/{category}/products/reorder', [ProductController::class, 'reorder']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::put('/products/{product}/availability', [ProductController::class, 'availability']);
        Route::put('/products/{product}/channels', [ProductController::class, 'channels']);
        Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/variants', [VariantController::class, 'store']);
        Route::post('/products/{product}/variants/reorder', [VariantController::class, 'reorder']);
        Route::put('/products/{product}/variants/{variant}', [VariantController::class, 'update']);
        Route::delete('/products/{product}/variants/{variant}', [VariantController::class, 'destroy']);
        Route::post('/modifier-groups', [ModifierController::class, 'storeGroup']);
        Route::put('/modifier-groups/{group}', [ModifierController::class, 'updateGroup']);
        Route::delete('/modifier-groups/{group}', [ModifierController::class, 'destroyGroup']);
        Route::post('/modifier-groups/{group}/options', [ModifierController::class, 'storeOption']);
        Route::put('/modifier-groups/{group}/options/{option}', [ModifierController::class, 'updateOption']);
        Route::delete('/modifier-groups/{group}/options/{option}', [ModifierController::class, 'destroyOption']);
        Route::post('/products/{product}/modifier-groups', [ModifierController::class, 'assign']);
        Route::delete('/products/{product}/modifier-groups/{assignment}', [ModifierController::class, 'unassign']);
        Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
        Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
    });
});
