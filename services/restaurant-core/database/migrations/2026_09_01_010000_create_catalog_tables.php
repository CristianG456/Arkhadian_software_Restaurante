<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('normalized_name', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('normalized_name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->boolean('allow_notes')->default(true);
            $table->unsignedInteger('sort_order');
            $table->timestampsTz();
            $table->unique(['category_id', 'normalized_name']);
            $table->index(['category_id', 'is_active', 'is_available', 'sort_order']);
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('normalized_name', 100)->nullable();
            $table->decimal('price', 14, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort_order');
            $table->timestampsTz();
            $table->unique(['product_id', 'normalized_name']);
            $table->index(['product_id', 'is_active', 'is_available', 'sort_order']);
        });

        Schema::create('modifier_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('normalized_name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('modifier_options', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('modifier_group_id')->constrained('modifier_groups')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('normalized_name', 100);
            $table->decimal('price_delta', 14, 2)->default(0);
            $table->unsignedInteger('sort_order');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['modifier_group_id', 'normalized_name']);
            $table->index(['modifier_group_id', 'is_active', 'sort_order']);
        });

        Schema::create('product_modifier_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('modifier_group_id')->constrained('modifier_groups')->restrictOnDelete();
            $table->unsignedSmallInteger('min_select')->default(0);
            $table->unsignedSmallInteger('max_select')->default(1);
            $table->unsignedInteger('sort_order');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['product_id', 'modifier_group_id']);
            $table->index(['product_id', 'is_active', 'sort_order']);
        });

        Schema::create('product_channel_visibilities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('channel', 32);
            $table->boolean('is_visible')->default(true);
            $table->timestampsTz();
            $table->unique(['product_id', 'channel']);
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('storage_path', 255)->unique();
            $table->string('mime_type', 32);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->boolean('is_primary')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestampsTz();
            $table->index(['product_id', 'sort_order']);
        });

        $this->addChecksAndPartialIndexes();
    }

    private function addChecksAndPartialIndexes(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE product_variants
            ADD CONSTRAINT product_variants_price_nonnegative CHECK (price >= 0)
        SQL);
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX product_variants_one_default_per_product
            ON product_variants (product_id) WHERE is_default = true
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE modifier_options
            ADD CONSTRAINT modifier_options_price_nonnegative CHECK (price_delta >= 0)
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE product_modifier_groups
            ADD CONSTRAINT product_modifier_groups_selection_check
            CHECK (min_select >= 0 AND max_select >= 1 AND min_select <= max_select)
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE product_channel_visibilities
            ADD CONSTRAINT product_channel_visibilities_channel_check
            CHECK (channel IN ('ADMIN_POS', 'WAITER', 'CUSTOMER_QR', 'PUBLIC_WEB'))
        SQL);
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX product_images_one_primary_per_product
            ON product_images (product_id) WHERE is_primary = true
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_channel_visibilities');
        Schema::dropIfExists('product_modifier_groups');
        Schema::dropIfExists('modifier_options');
        Schema::dropIfExists('modifier_groups');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
