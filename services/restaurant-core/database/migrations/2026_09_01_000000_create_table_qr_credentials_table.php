<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_qr_credentials', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('restaurant_table_id')
                ->constrained('restaurant_tables')
                ->restrictOnDelete();
            $table->string('public_token', 43)->unique();
            $table->unsignedInteger('version');
            $table->string('status', 16);
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->unique(['restaurant_table_id', 'version']);
            $table->index(['restaurant_table_id', 'status']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE table_qr_credentials
            ADD CONSTRAINT table_qr_credentials_status_check
            CHECK (status IN ('active', 'revoked'))
        SQL);
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX table_qr_credentials_one_active_per_table
            ON table_qr_credentials (restaurant_table_id)
            WHERE status = 'active'
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('table_qr_credentials');
    }
};
