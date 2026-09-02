<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_centers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('normalized_name', 100)->unique();
            $table->unsignedInteger('sort_order');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['is_active', 'sort_order']);
        });
        Schema::create('printers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('normalized_name', 100)->unique();
            $table->string('driver_mode', 32);
            $table->string('connection_type', 32);
            $table->string('system_name', 180)->nullable();
            $table->string('device_path', 255)->nullable();
            $table->unsignedSmallInteger('paper_width')->default(80);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default_fallback')->default(false);
            $table->boolean('is_preaccount_default')->default(false);
            $table->timestampsTz();
            $table->index(['is_active', 'driver_mode']);
        });
        Schema::create('production_center_printers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('production_center_id')->constrained('production_centers')->cascadeOnDelete();
            $table->foreignUlid('printer_id')->constrained('printers')->restrictOnDelete();
            $table->unsignedSmallInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['production_center_id', 'printer_id']);
            $table->unique(['production_center_id', 'priority']);
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignUlid('production_center_id')->nullable()->after('category_id')->constrained('production_centers')->nullOnDelete();
            $table->boolean('requires_production')->default(true)->after('allow_notes');
            $table->index(['requires_production', 'production_center_id']);
        });
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->foreignUlid('production_center_id')->nullable()->after('product_id')->constrained('production_centers')->nullOnDelete();
        });
        Schema::table('table_sessions', function (Blueprint $table): void {
            $table->timestampTz('bill_requested_at')->nullable()->after('closed_reason');
            $table->string('bill_request_source', 24)->nullable()->after('bill_requested_at');
        });
        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->string('permission', 80);
            $table->boolean('is_granted')->default(true);
            $table->timestampsTz();
            $table->unique(['staff_member_id', 'permission']);
        });
        Schema::create('print_jobs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('job_type', 40);
            $table->foreignUlid('printer_id')->nullable()->constrained('printers')->nullOnDelete();
            $table->foreignUlid('production_center_id')->nullable()->constrained('production_centers')->nullOnDelete();
            $table->foreignUlid('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->foreignUlid('table_session_id')->nullable()->constrained('table_sessions')->restrictOnDelete();
            $table->string('idempotency_key', 180)->unique();
            $table->jsonb('payload_snapshot');
            $table->string('status', 20);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(3);
            $table->string('last_error_code', 80)->nullable();
            $table->string('last_error_message', 500)->nullable();
            $table->ulid('requested_by_actor_id')->nullable();
            $table->string('request_reason', 500)->nullable();
            $table->timestampTz('available_at')->nullable();
            $table->timestampTz('processing_at')->nullable();
            $table->timestampTz('printed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'available_at', 'created_at']);
            $table->index(['order_id', 'job_type']);
            $table->index(['table_session_id', 'job_type']);
        });
        DB::statement('CREATE UNIQUE INDEX printers_one_preaccount_default ON printers (is_preaccount_default) WHERE is_preaccount_default = true');
        DB::statement("ALTER TABLE printers ADD CONSTRAINT printers_driver_check CHECK (driver_mode IN ('TEST_FILE', 'WINDOWS_RAW_SHARE'))");
        DB::statement("ALTER TABLE printers ADD CONSTRAINT printers_connection_check CHECK (connection_type IN ('FILE', 'WINDOWS_SHARE', 'USB', 'SERIAL', 'PARALLEL'))");
        DB::statement('ALTER TABLE printers ADD CONSTRAINT printers_paper_check CHECK (paper_width IN (58, 80))');
        DB::statement("ALTER TABLE table_sessions ADD CONSTRAINT table_sessions_bill_source_check CHECK (bill_request_source IS NULL OR bill_request_source IN ('WAITER', 'ADMIN_POS', 'CUSTOMER_QR'))");
        DB::statement("ALTER TABLE print_jobs ADD CONSTRAINT print_jobs_status_check CHECK (status IN ('PENDING', 'PROCESSING', 'PRINTED', 'FAILED'))");
        DB::statement("ALTER TABLE print_jobs ADD CONSTRAINT print_jobs_type_check CHECK (job_type IN ('ORDER_TICKET', 'ORDER_REPRINT', 'CUSTOMER_REQUEST_TICKET', 'TABLE_TRANSFER_NOTICE', 'ORDER_CANCEL_NOTICE', 'PREACCOUNT', 'TEST_PRINT'))");
        DB::statement('ALTER TABLE print_jobs ADD CONSTRAINT print_jobs_attempts_check CHECK (max_attempts BETWEEN 1 AND 10 AND attempts <= max_attempts)');
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
        Schema::dropIfExists('staff_permissions');
        Schema::table('table_sessions', fn (Blueprint $table) => $table->dropColumn(['bill_requested_at', 'bill_request_source']));
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropConstrainedForeignId('production_center_id'));
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('production_center_id');
            $table->dropColumn('requires_production');
        });
        Schema::dropIfExists('production_center_printers');
        Schema::dropIfExists('printers');
        Schema::dropIfExists('production_centers');
    }
};
