<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Production\Models\Printer;
use App\Domain\Production\Models\PrintJob;
use App\Domain\Production\Models\ProductionCenter;
use App\Domain\Production\Services\PrintJobProcessor;
use App\Domain\Production\Services\ProductionRoutingService;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionPrintingApiTest extends TestCase
{
    use RefreshDatabase;

    private function printer(bool $preaccount = false, ?string $path = null): Printer
    {
        return Printer::factory()->create(['is_preaccount_default' => $preaccount, 'device_path' => $path]);
    }

    private function centerWithPrinter(Printer $printer, string $name): ProductionCenter
    {
        $name .= ' '.Str::lower(Str::random(6));
        $center = ProductionCenter::factory()->create(['name' => $name, 'normalized_name' => mb_strtolower($name)]);
        $center->printers()->attach($printer->id, ['id' => (string) Str::ulid(), 'priority' => 1, 'is_active' => true]);

        return $center;
    }

    private function product(ProductionCenter $center, string $name, string $price = '10000.00'): array
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => $name, 'normalized_name' => mb_strtolower($name), 'production_center_id' => $center->id, 'requires_production' => true]);
        $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price' => $price]);
        $product->channels()->create(['channel' => 'ADMIN_POS', 'is_visible' => true]);

        return [$product, $variant];
    }

    private function order(array $lines): Order
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        $items = collect($lines)->map(fn ($line) => ['productId' => $line[0]->id, 'variantId' => $line[1]->id, 'quantity' => 1, 'modifierOptionIds' => []])->all();
        $id = $this->postJson('/api/orders', ['tableId' => $table->id, 'assignedWaiterId' => $waiter->id, 'clientOperationId' => (string) Str::ulid(), 'items' => $items])->assertCreated()->json('data.order.id');

        return Order::query()->findOrFail($id);
    }

    public function test_centers_printers_assignment_and_test_job_are_configurable(): void
    {
        $name = 'Cocina API '.Str::lower(Str::random(6));
        $centerId = $this->postJson('/api/production/centers', ['name' => ' '.$name.' '])->assertCreated()->json('data.center.id');
        $this->postJson('/api/production/centers', ['name' => mb_strtoupper($name)])->assertUnprocessable();
        $printerId = $this->postJson('/api/production/printers', ['name' => 'Comandas', 'driverMode' => 'TEST_FILE', 'connectionType' => 'FILE', 'paperWidth' => 80])->assertCreated()->json('data.printer.id');
        $this->putJson('/api/production/centers/'.$centerId.'/printer', ['printerId' => $printerId])->assertOk()->assertJsonPath('data.center.printers.0.id', $printerId);
        $this->postJson('/api/production/printers/'.$printerId.'/test')->assertCreated()->assertJsonPath('data.printJob.status', 'PENDING');
    }

    public function test_routing_groups_items_by_center_and_is_idempotent(): void
    {
        $printer = $this->printer();
        $kitchen = $this->centerWithPrinter($printer, 'Cocina');
        $drinks = $this->centerWithPrinter($printer, 'Bebidas');
        $order = $this->order([$this->product($kitchen, 'Filet'), $this->product($kitchen, 'Hamburguesa'), $this->product($drinks, 'Limonada')]);
        $service = app(ProductionRoutingService::class);
        $service->routeOrder($order->id);
        $service->routeOrder($order->id);
        $this->assertSame(2, PrintJob::query()->where('order_id', $order->id)->where('job_type', PrintJob::TYPE_ORDER)->count());
        $this->assertCount(2, PrintJob::query()->where('production_center_id', $kitchen->id)->firstOrFail()->payload_snapshot['items']);
    }

    public function test_missing_center_does_not_rollback_order(): void
    {
        $center = ProductionCenter::factory()->create();
        [$product, $variant] = $this->product($center, 'Sin impresora');
        $order = $this->order([[$product, $variant]]);
        app(ProductionRoutingService::class)->routeOrder($order->id);
        $this->assertSame('CONFIRMED', $order->status);
        $this->assertDatabaseHas('print_jobs', ['order_id' => $order->id, 'status' => 'FAILED', 'last_error_code' => 'PRINTER_NOT_CONFIGURED']);
    }

    public function test_worker_prints_with_fake_driver_and_recovers_failed_job(): void
    {
        $printer = $this->printer(false, 'fail://always');
        $center = $this->centerWithPrinter($printer, 'Cocina');
        $order = $this->order([$this->product($center, 'Filet')]);
        app(ProductionRoutingService::class)->routeOrder($order->id);
        $processor = app(PrintJobProcessor::class);
        $job = $processor->processNext();
        $this->assertSame('PENDING', $job->status);
        $job->update(['available_at' => now()]);
        $processor->processNext();
        $job->refresh()->update(['available_at' => now()]);
        $processor->processNext();
        $this->assertSame('FAILED', $job->refresh()->status);
        $printer->update(['device_path' => null]);
        $processor->retry($job);
        $processor->processNext();
        $this->assertSame('PRINTED', $job->refresh()->status);
    }

    public function test_reprint_creates_a_distinct_marked_job(): void
    {
        $printer = $this->printer();
        $center = $this->centerWithPrinter($printer, 'Cocina');
        $order = $this->order([$this->product($center, 'Filet')]);
        app(ProductionRoutingService::class)->routeOrder($order->id);
        $this->postJson('/api/orders/'.$order->id.'/reprint', ['reason' => 'Ticket ilegible'])->assertCreated()->assertJsonPath('data.printJobs.0.jobType', 'ORDER_REPRINT');
        $this->assertDatabaseCount('print_jobs', 2);
    }

    public function test_preaccount_uses_active_snapshots_and_does_not_close_session(): void
    {
        $printer = $this->printer(true);
        $center = $this->centerWithPrinter($printer, 'Cocina');
        $order = $this->order([$this->product($center, 'Filet', '45000')]);
        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();
        $this->postJson('/api/order-items/'.$item->id.'/cancel', ['reason' => 'Cambio del cliente'])->assertOk();
        $this->getJson('/api/table-sessions/'.$order->table_session_id.'/preaccount')->assertOk()->assertJsonPath('data.preaccount.total', '0.00')->assertJsonPath('data.preaccount.notice', 'PRECUENTA · NO ES FACTURA');
        $this->postJson('/api/table-sessions/'.$order->table_session_id.'/preaccount/print')->assertCreated()->assertJsonPath('data.printJob.jobType', 'PREACCOUNT');
        $this->assertSame(TableSession::STATUS_OPEN, $order->session->refresh()->status);
    }

    public function test_request_bill_marks_open_session_without_closing_it(): void
    {
        $printer = $this->printer();
        $center = $this->centerWithPrinter($printer, 'Cocina');
        $order = $this->order([$this->product($center, 'Filet')]);
        $this->postJson('/api/table-sessions/'.$order->table_session_id.'/request-bill')->assertOk()->assertJsonPath('data.source', 'ADMIN_POS');
        $this->assertNotNull($order->session->refresh()->bill_requested_at);
        $this->assertSame('OPEN', $order->session->status);
    }
}
