<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_installations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('restaurant_code', 24)->unique();
            $table->string('restaurant_name', 120);
            $table->string('operating_mode', 12)->default('LOCAL');
            $table->string('primary_color', 16)->nullable();
            $table->timestampsTz();
        });

        Schema::table('staff_members', function (Blueprint $table): void {
            $table->string('username', 60)->nullable()->unique();
            $table->string('pin_hash')->nullable();
        });

        Schema::create('waiter_devices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUuid('installation_id')->constrained('restaurant_installations')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('platform', 24);
            $table->uuid('device_identifier');
            $table->string('credential_hash', 64)->unique();
            $table->string('status', 16)->default('ACTIVE');
            $table->timestampTz('paired_at');
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->unique(['installation_id', 'device_identifier']);
            $table->index(['installation_id', 'status']);
        });

        Schema::create('device_pairings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUuid('installation_id')->constrained('restaurant_installations')->cascadeOnDelete();
            $table->string('code_hash');
            $table->string('token_hash', 64)->unique();
            $table->unsignedSmallInteger('max_uses')->default(1);
            $table->unsignedSmallInteger('use_count')->default(0);
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();
            $table->index(['installation_id', 'expires_at']);
        });

        Schema::create('waiter_access_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignUlid('device_id')->constrained('waiter_devices')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->index(['staff_member_id', 'revoked_at']);
            $table->index(['device_id', 'revoked_at']);
        });

        Schema::create('waiter_notifications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('recipient_staff_id')->constrained('staff_members')->cascadeOnDelete();
            $table->string('type', 48);
            $table->jsonb('payload');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->index(['recipient_staff_id', 'read_at', 'created_at']);
        });

        DB::statement("ALTER TABLE restaurant_installations ADD CONSTRAINT restaurant_installations_mode_check CHECK (operating_mode IN ('LOCAL', 'CLOUD'))");
        DB::statement("ALTER TABLE waiter_devices ADD CONSTRAINT waiter_devices_status_check CHECK (status IN ('ACTIVE', 'REVOKED'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('waiter_notifications');
        Schema::dropIfExists('waiter_access_tokens');
        Schema::dropIfExists('device_pairings');
        Schema::dropIfExists('waiter_devices');
        Schema::table('staff_members', function (Blueprint $table): void {
            $table->dropColumn(['username', 'pin_hash']);
        });
        Schema::dropIfExists('restaurant_installations');
    }
};
