<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('normalized_name', 100)->unique();
            $table->string('staff_type', 24);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['staff_type', 'is_active']);
        });

        Schema::create('table_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('table_id')->constrained('restaurant_tables')->restrictOnDelete();
            $table->foreignUlid('assigned_waiter_id')->constrained('staff_members')->restrictOnDelete();
            $table->string('status', 16);
            $table->timestampTz('opened_at');
            $table->timestampTz('closed_at')->nullable();
            $table->string('closed_reason', 500)->nullable();
            $table->ulid('opened_by_actor_id')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'opened_at']);
            $table->index(['assigned_waiter_id', 'status']);
        });

        Schema::create('table_waiter_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('table_session_id')->constrained('table_sessions')->restrictOnDelete();
            $table->foreignUlid('from_waiter_id')->nullable()->constrained('staff_members')->restrictOnDelete();
            $table->foreignUlid('to_waiter_id')->constrained('staff_members')->restrictOnDelete();
            $table->ulid('performed_by_actor_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['table_session_id', 'created_at']);
        });

        DB::statement('CREATE SEQUENCE IF NOT EXISTS order_number_seq START 1');
        Schema::create('orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('order_number')->unique();
            $table->foreignUlid('table_session_id')->constrained('table_sessions')->restrictOnDelete();
            $table->foreignUlid('responsible_waiter_id')->constrained('staff_members')->restrictOnDelete();
            $table->string('source', 24);
            $table->string('status', 16);
            $table->ulid('client_operation_id')->unique();
            $table->ulid('created_by_actor_id')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['table_session_id', 'created_at']);
            $table->index(['responsible_waiter_id', 'created_at']);
            $table->index(['source', 'created_at']);
        });
        DB::statement("ALTER TABLE orders ALTER COLUMN order_number SET DEFAULT nextval('order_number_seq')");

        DB::statement('ALTER SEQUENCE order_number_seq OWNED BY orders.order_number');

        Schema::create('order_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->string('product_name_snapshot', 150);
            $table->string('variant_name_snapshot', 100)->nullable();
            $table->decimal('unit_price_snapshot', 14, 2);
            $table->decimal('line_base_total_snapshot', 14, 2);
            $table->text('notes')->nullable();
            $table->string('status', 16);
            $table->timestampTz('cancelled_at')->nullable();
            $table->ulid('cancelled_by_actor_id')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['order_id', 'status']);
            $table->index('product_id');
        });

        Schema::create('order_item_modifiers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignUlid('modifier_group_id')->nullable()->constrained('modifier_groups')->nullOnDelete();
            $table->foreignUlid('modifier_option_id')->nullable()->constrained('modifier_options')->nullOnDelete();
            $table->string('group_name_snapshot', 100);
            $table->string('option_name_snapshot', 100);
            $table->decimal('price_delta_snapshot', 14, 2);
            $table->timestampsTz();
            $table->index('order_item_id');
        });

        Schema::create('table_session_table_transfers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('table_session_id')->constrained('table_sessions')->restrictOnDelete();
            $table->foreignUlid('from_table_id')->constrained('restaurant_tables')->restrictOnDelete();
            $table->foreignUlid('to_table_id')->constrained('restaurant_tables')->restrictOnDelete();
            $table->ulid('performed_by_actor_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['table_session_id', 'created_at']);
        });

        DB::statement("CREATE UNIQUE INDEX table_sessions_one_open_per_table ON table_sessions (table_id) WHERE status = 'OPEN'");
        DB::statement("ALTER TABLE staff_members ADD CONSTRAINT staff_members_type_check CHECK (staff_type IN ('WAITER'))");
        DB::statement("ALTER TABLE table_sessions ADD CONSTRAINT table_sessions_status_check CHECK (status IN ('OPEN', 'CLOSED'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_source_check CHECK (source IN ('WAITER', 'ADMIN_POS', 'CUSTOMER_QR'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('CONFIRMED', 'CANCELLED'))");
        DB::statement("ALTER TABLE order_items ADD CONSTRAINT order_items_status_check CHECK (status IN ('ACTIVE', 'CANCELLED'))");
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_check CHECK (quantity BETWEEN 1 AND 100)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_money_check CHECK (unit_price_snapshot >= 0 AND line_base_total_snapshot >= 0)');
        DB::statement('ALTER TABLE order_item_modifiers ADD CONSTRAINT order_item_modifiers_money_check CHECK (price_delta_snapshot >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('table_session_table_transfers');
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        DB::statement('DROP SEQUENCE IF EXISTS order_number_seq');
        Schema::dropIfExists('table_waiter_assignments');
        Schema::dropIfExists('table_sessions');
        Schema::dropIfExists('staff_members');
    }
};
