<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_simple_product_atomically_with_decimal_money_and_channels(): void
    {
        $category = ProductCategory::factory()->create();
        $response = $this->postJson('/api/catalog/products', ['categoryId' => $category->id, 'name' => '  Coca-Cola  ', 'price' => '999999.99']);
        $response->assertCreated()->assertJsonPath('data.product.name', 'Coca-Cola')->assertJsonPath('data.product.basePrice', '999999.99')->assertJsonCount(1, 'data.product.variants')->assertJsonCount(4, 'data.product.channels');
        $product = Product::query()->firstOrFail();
        $this->assertSame(1, $product->variants()->count());
        $this->assertTrue($product->defaultVariant()->firstOrFail()->is_default);
    }

    public function test_category_and_product_names_are_normalized_and_duplicates_rejected(): void
    {
        $this->postJson('/api/catalog/categories', ['name' => ' Bebidas '])->assertCreated();
        $this->postJson('/api/catalog/categories', ['name' => 'bebidas'])->assertStatus(409)->assertJsonPath('error.code', 'CATEGORY_ALREADY_EXISTS');
        $category = ProductCategory::query()->firstOrFail();
        $this->postJson('/api/catalog/products', ['categoryId' => $category->id, 'name' => 'Café', 'price' => '5000'])->assertCreated();
        $this->postJson('/api/catalog/products', ['categoryId' => $category->id, 'name' => ' café ', 'price' => '5000'])->assertStatus(409);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->default()->create(['product_id' => $product->id]);
        $this->deleteJson('/api/catalog/categories/'.$product->category_id)->assertStatus(409)->assertJsonPath('error.code', 'CATEGORY_NOT_EMPTY');
    }

    public function test_variant_invariants_and_default_switch_are_enforced(): void
    {
        $product = Product::factory()->create();
        $default = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price' => '5000.00']);
        $second = $this->postJson('/api/catalog/products/'.$product->id.'/variants', ['name' => 'Grande', 'price' => '9000'])->assertCreated()->json('data.variant.id');
        $this->putJson('/api/catalog/products/'.$product->id.'/variants/'.$second, ['isDefault' => true])->assertOk()->assertJsonPath('data.variant.isDefault', true);
        $this->assertFalse($default->refresh()->is_default);
        $this->deleteJson('/api/catalog/products/'.$product->id.'/variants/'.$second)->assertStatus(409);
        $this->deleteJson('/api/catalog/products/'.$product->id.'/variants/'.$default->id)->assertOk();
    }

    public function test_product_duplication_gets_new_aggregate_ids(): void
    {
        $product = Product::factory()->create(['name' => 'Pizza', 'normalized_name' => 'pizza']);
        ProductVariant::factory()->default()->create(['product_id' => $product->id]);
        foreach (['ADMIN_POS', 'WAITER', 'CUSTOMER_QR', 'PUBLIC_WEB'] as $channel) {
            $product->channels()->create(['channel' => $channel, 'is_visible' => true]);
        }
        $response = $this->postJson('/api/catalog/products/'.$product->id.'/duplicate')->assertCreated();
        $this->assertNotSame($product->id, $response->json('data.product.id'));
        $this->assertSame(2, Product::query()->count());
        $this->assertSame(2, ProductVariant::query()->count());
    }

    public function test_modifier_group_can_be_reused_and_invalid_selection_is_rejected(): void
    {
        $group = ModifierGroup::factory()->create();
        $group->options()->create(['name' => 'Queso', 'normalized_name' => 'queso', 'price_delta' => '3000.00', 'sort_order' => 1, 'is_active' => true]);
        $products = Product::factory()->count(2)->create();
        foreach ($products as $product) {
            $this->postJson('/api/catalog/products/'.$product->id.'/modifier-groups', ['modifierGroupId' => $group->id, 'minSelect' => 0, 'maxSelect' => 1])->assertCreated();
        }
        $this->postJson('/api/catalog/products/'.$products[0]->id.'/modifier-groups', ['modifierGroupId' => $group->id, 'minSelect' => 2, 'maxSelect' => 1])->assertStatus(422);
        $this->deleteJson('/api/catalog/modifier-groups/'.$group->id)->assertStatus(409);
    }

    public function test_channel_visibility_requires_the_complete_fixed_set(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->default()->create(['product_id' => $product->id]);
        $this->putJson('/api/catalog/products/'.$product->id.'/channels', ['channels' => ['ADMIN_POS' => true]])->assertStatus(422);
        $this->putJson('/api/catalog/products/'.$product->id.'/channels', ['channels' => ['ADMIN_POS' => true, 'WAITER' => true, 'CUSTOMER_QR' => false, 'PUBLIC_WEB' => false]])->assertOk()->assertJsonPath('data.product.channels.CUSTOMER_QR', false);
    }

    public function test_writes_are_rejected_outside_loopback(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])->postJson('/api/catalog/categories', ['name' => 'Privada'])->assertForbidden()->assertJsonPath('error.code', 'LOCAL_ADMIN_ONLY');
    }
}
