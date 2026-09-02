<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogImagesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_png_is_private_random_and_deletable_without_exposing_path(): void
    {
        Storage::fake('local');
        $product = Product::factory()->create();
        ProductVariant::factory()->default()->create(['product_id' => $product->id]);
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        $response = $this->post('/api/catalog/products/'.$product->id.'/images', [
            'image' => UploadedFile::fake()->createWithContent('menu.png', $bytes),
        ]);

        $response->assertCreated()->assertJsonMissingPath('data.image.storagePath');
        $image = $product->images()->firstOrFail();
        $this->assertStringNotContainsString('menu.png', $image->storage_path);
        Storage::disk('local')->assertExists($image->storage_path);
        $this->deleteJson('/api/catalog/products/'.$product->id.'/images/'.$image->id)->assertOk();
        Storage::disk('local')->assertMissing($image->storage_path);
    }

    public function test_svg_and_fake_jpg_are_rejected(): void
    {
        Storage::fake('local');
        $product = Product::factory()->create();
        ProductVariant::factory()->default()->create(['product_id' => $product->id]);

        $this->post('/api/catalog/products/'.$product->id.'/images', [
            'image' => UploadedFile::fake()->createWithContent('attack.svg', '<svg/>'),
        ])->assertStatus(422);
        $this->post('/api/catalog/products/'.$product->id.'/images', [
            'image' => UploadedFile::fake()->createWithContent('fake.jpg', 'not an image'),
        ])->assertStatus(422);
        $this->assertSame(0, $product->images()->count());
    }
}
