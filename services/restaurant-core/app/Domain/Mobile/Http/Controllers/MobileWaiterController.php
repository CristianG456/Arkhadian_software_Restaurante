<?php

namespace App\Domain\Mobile\Http\Controllers;

use App\Domain\Areas\Models\Area;
use App\Domain\Catalog\Http\Resources\CatalogResources;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Mobile\Models\WaiterNotification;
use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Orders\Http\Resources\OrderResources;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Services\OrderService;
use App\Domain\Orders\Services\TableSessionService;
use App\Domain\Orders\Support\DecimalMoney;
use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Production\Services\PreaccountService;
use App\Domain\Production\Services\ProductionRoutingService;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Staff\Services\OperationalPermissionService;
use App\Domain\Tables\Models\RestaurantTable;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiException;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileWaiterController extends Controller
{
    public function __construct(private readonly OperationalPermissionService $permissions, private readonly TableSessionService $sessions) {}

    public function tables(Request $request): JsonResponse
    {
        $waiter = $this->waiter($request);
        $tables = TableSession::query()->where('assigned_waiter_id', $waiter->id)->where('status', TableSession::STATUS_OPEN)->with(['table.area', 'orders.items.modifiers'])->withCount('orders')->orderBy('opened_at')->get()->map(fn (TableSession $session) => ['sessionId' => $session->id, 'areaId' => $session->table->area_id, 'areaName' => $session->table->area->name, 'tableId' => $session->table_id, 'tableLabel' => $session->table->label, 'openedAt' => $session->opened_at->toIso8601String(), 'currentTotal' => DecimalMoney::fromMinor($this->sessions->totalMinor($session)), 'ordersCount' => $session->orders_count, 'billRequested' => $session->bill_requested_at !== null, 'customerRequestCount' => $session->orders->where('source.value', 'CUSTOMER_QR')->count()])->all();

        return ApiResponse::success(['tables' => $tables]);
    }

    public function availableTables(Request $request): JsonResponse
    {
        $this->waiter($request);
        $sessions = TableSession::query()->where('status', TableSession::STATUS_OPEN)->with('waiter')->get()->keyBy('table_id');
        $areas = Area::query()->where('is_active', true)->with(['tables' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])->orderBy('sort_order')->get()->map(fn (Area $area) => ['id' => $area->id, 'name' => $area->name, 'tables' => $area->tables->map(function (RestaurantTable $table) use ($sessions): array {
            $session = $sessions->get($table->id);

            return ['id' => $table->id, 'label' => $table->label, 'state' => $session ? 'OCCUPIED' : 'FREE', 'waiterName' => $session?->waiter->name];
        })->all()])->all();

        return ApiResponse::success(['areas' => $areas]);
    }

    public function catalog(Request $request): JsonResponse
    {
        $this->waiter($request);
        $categories = ProductCategory::query()->where('is_active', true)->whereHas('products', fn ($query) => $query->where('is_active', true)->whereHas('channels', fn ($channel) => $channel->where('channel', 'WAITER')->where('is_visible', true)))->with(['products' => fn ($query) => $query->where('is_active', true)->whereHas('channels', fn ($channel) => $channel->where('channel', 'WAITER')->where('is_visible', true)), 'products.variants' => fn ($query) => $query->where('is_active', true), 'products.primaryImage', 'products.channels', 'products.images', 'products.modifierAssignments' => fn ($query) => $query->where('is_active', true), 'products.modifierAssignments.group.options' => fn ($query) => $query->where('is_active', true), 'products.productionCenter'])->orderBy('sort_order')->get();

        return ApiResponse::success(['categories' => $categories->map(fn (ProductCategory $category) => ['id' => $category->id, 'name' => $category->name, 'products' => $category->products->map(fn ($product) => CatalogResources::product($product, true))->all()])->all()]);
    }

    public function staff(Request $request): JsonResponse
    {
        $waiter = $this->waiter($request);
        $staff = StaffMember::query()->where('staff_type', StaffMember::TYPE_WAITER)->where('is_active', true)->whereKeyNot($waiter->id)->orderBy('name')->get()->map(fn (StaffMember $person) => ['id' => $person->id, 'name' => $person->name])->all();

        return ApiResponse::success(['staff' => $staff]);
    }

    public function session(Request $request, TableSession $tableSession): JsonResponse
    {
        $this->own($this->waiter($request), $tableSession);

        return ApiResponse::success(['session' => OrderResources::session($this->sessions->hydrate($tableSession), $this->sessions), 'permissions' => $this->permissionList($this->waiter($request))]);
    }

    public function orderForTable(Request $request, RestaurantTable $table, OrderService $orders): JsonResponse
    {
        $waiter = $this->waiter($request);
        $data = $this->orderData($request) + ['tableId' => $table->id];

        return ApiResponse::success(['order' => OrderResources::order($orders->createWaiter($data, $waiter))], 201);
    }

    public function orderForSession(Request $request, TableSession $tableSession, OrderService $orders): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->own($waiter, $tableSession);
        $data = $this->orderData($request) + ['tableId' => $tableSession->table_id];

        return ApiResponse::success(['order' => OrderResources::order($orders->createWaiter($data, $waiter))], 201);
    }

    public function transfer(Request $request, TableSession $tableSession): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->allow($waiter, 'tables.transfer_own');
        $this->own($waiter, $tableSession);
        $data = $request->validate(['waiterId' => ['required', 'ulid', 'exists:staff_members,id'], 'reason' => ['nullable', 'string', 'max:500']]);

        return ApiResponse::success(['session' => OrderResources::session($this->sessions->transferWaiter($tableSession, $data['waiterId'], $data['reason'] ?? null), $this->sessions)]);
    }

    public function move(Request $request, TableSession $tableSession): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->allow($waiter, 'tables.move_own');
        $this->own($waiter, $tableSession);
        $data = $request->validate(['targetTableId' => ['required', 'ulid', 'exists:restaurant_tables,id'], 'reason' => ['nullable', 'string', 'max:500']]);

        return ApiResponse::success(['session' => OrderResources::session($this->sessions->move($tableSession, $data['targetTableId'], $data['reason'] ?? null), $this->sessions)]);
    }

    public function requestBill(Request $request, TableSession $tableSession, PreaccountService $preaccount): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->own($waiter, $tableSession);
        $session = $preaccount->requestBill($tableSession, OrderSource::WAITER);

        return ApiResponse::success(['billRequestedAt' => $session->bill_requested_at->toIso8601String()]);
    }

    public function preaccount(Request $request, TableSession $tableSession, PreaccountService $preaccount): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->allow($waiter, 'preaccount.view');
        $this->own($waiter, $tableSession);

        return ApiResponse::success(['preaccount' => $preaccount->preview($tableSession)]);
    }

    public function printPreaccount(Request $request, TableSession $tableSession, PreaccountService $preaccount): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->allow($waiter, 'preaccount.print');
        $this->own($waiter, $tableSession);

        return ApiResponse::success(['printJob' => ProductionResources::job($preaccount->print($tableSession)->load(['printer', 'center']))], 201);
    }

    public function reprint(Request $request, Order $order, ProductionRoutingService $routing): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->allow($waiter, 'orders.reprint');
        $this->own($waiter, $order->session);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $jobs = collect($routing->reprint($order, null, $data['reason'] ?? null))->map(fn ($job) => ProductionResources::job($job->load(['printer', 'center'])))->all();

        return ApiResponse::success(['printJobs' => $jobs], 201);
    }

    public function cancelItem(Request $request, OrderItem $orderItem, OrderService $orders): JsonResponse
    {
        $waiter = $this->waiter($request);
        $this->allow($waiter, 'orders.cancel');
        $this->own($waiter, $orderItem->order->session);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        return ApiResponse::success(['item' => OrderResources::item($orders->cancelItem($orderItem, $data['reason']))]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $waiter = $this->waiter($request);
        $alerts = WaiterNotification::query()->where('recipient_staff_id', $waiter->id)->latest()->limit(100)->get()->map(fn (WaiterNotification $notification) => ['id' => $notification->id, 'type' => $notification->type, 'payload' => $notification->payload, 'readAt' => $notification->read_at?->toIso8601String(), 'createdAt' => $notification->created_at->toIso8601String()])->all();

        return ApiResponse::success(['alerts' => $alerts]);
    }

    public function readAlert(Request $request, WaiterNotification $notification): JsonResponse
    {
        $waiter = $this->waiter($request);
        if ($notification->recipient_staff_id !== $waiter->id) {
            throw new ApiException('PERMISSION_DENIED', 'Esta alerta pertenece a otro mesero.', 403);
        }
        $notification->update(['read_at' => now()]);

        return ApiResponse::success(['read' => true]);
    }

    private function waiter(Request $request): StaffMember
    {
        return $request->attributes->get('waiter');
    }

    private function own(StaffMember $waiter, TableSession $session): void
    {
        if ($session->assigned_waiter_id !== $waiter->id) {
            throw new ApiException('TABLE_ASSIGNED_TO_ANOTHER_WAITER', 'Esta mesa pertenece a otro mesero.', 403);
        }
    }

    private function allow(StaffMember $waiter, string $permission): void
    {
        if (! $this->permissions->allows($waiter, $permission)) {
            throw new ApiException('PERMISSION_DENIED', 'No tienes permiso para esta operación.', 403);
        }
    }

    private function permissionList(StaffMember $waiter): array
    {
        return collect([...OperationalPermissionService::DEFAULT_WAITER, ...OperationalPermissionService::CONFIGURABLE])->filter(fn (string $permission) => $this->permissions->allows($waiter, $permission))->values()->all();
    }

    private function orderData(Request $request): array
    {
        return $request->validate(['clientOperationId' => ['required', 'ulid'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.productId' => ['required', 'ulid', 'exists:products,id'], 'items.*.variantId' => ['nullable', 'ulid', 'exists:product_variants,id'], 'items.*.quantity' => ['required', 'integer', 'between:1,100'], 'items.*.modifierOptionIds' => ['sometimes', 'array'], 'items.*.modifierOptionIds.*' => ['ulid', 'distinct'], 'items.*.notes' => ['nullable', 'string', 'max:1000']]);
    }
}
