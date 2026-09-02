<?php

namespace App\Domain\Staff\Http\Controllers;

use App\Domain\Orders\Http\Requests\StoreOrderRequest;
use App\Domain\Orders\Http\Resources\OrderResources;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Services\OrderService;
use App\Domain\Orders\Services\TableSessionService;
use App\Domain\Orders\Support\DecimalMoney;
use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Production\Services\PreaccountService;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Staff\Services\OperationalPermissionService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiException;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class WaiterOperationController extends Controller
{
    public function __construct(private readonly OperationalPermissionService $permissions) {}

    public function tables(StaffMember $staffMember, TableSessionService $sessions): JsonResponse
    {
        $this->ensure($staffMember, 'orders.view_own');
        $data = TableSession::query()->where('assigned_waiter_id', $staffMember->id)->where('status', TableSession::STATUS_OPEN)->with(['table.area', 'waiter', 'orders.items.modifiers'])->get()->map(fn ($session) => ['id' => $session->id, 'area' => $session->table->area->name, 'table' => $session->table->label, 'openedAt' => $session->opened_at->toIso8601String(), 'billRequestedAt' => $session->bill_requested_at?->toIso8601String(), 'total' => DecimalMoney::fromMinor($sessions->totalMinor($session))])->all();

        return ApiResponse::success(['tables' => $data, 'permissions' => collect(array_merge(OperationalPermissionService::DEFAULT_WAITER, OperationalPermissionService::CONFIGURABLE))->filter(fn ($permission) => $this->permissions->allows($staffMember, $permission))->values()->all()]);
    }

    public function order(StoreOrderRequest $request, StaffMember $staffMember, OrderService $orders): JsonResponse
    {
        $this->ensure($staffMember, 'orders.create');

        return ApiResponse::success(['order' => OrderResources::order($orders->createWaiter($request->validated(), $staffMember))], 201);
    }

    public function preaccount(StaffMember $staffMember, TableSession $tableSession, PreaccountService $service): JsonResponse
    {
        $this->ensureOwn($staffMember, $tableSession, 'preaccount.print');

        return ApiResponse::success(['printJob' => ProductionResources::job($service->print($tableSession)->load(['printer', 'center']))], 201);
    }

    private function ensure(StaffMember $staff, string $permission): void
    {
        if (! $this->permissions->allows($staff, $permission)) {
            throw new ApiException('PERMISSION_DENIED', 'No tienes permiso para esta operación.', 403);
        }
    }

    private function ensureOwn(StaffMember $staff, TableSession $session, string $permission): void
    {
        $this->ensure($staff, $permission);
        if ($session->assigned_waiter_id !== $staff->id) {
            throw new ApiException('TABLE_ASSIGNED_TO_ANOTHER_WAITER', 'Esta mesa pertenece a otro mesero.', 403);
        }
    }
}
