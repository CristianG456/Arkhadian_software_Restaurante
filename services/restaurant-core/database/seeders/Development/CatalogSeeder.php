<?php

namespace Database\Seeders\Development;

use App\Domain\Catalog\Enums\ProductChannel;
use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = [];
            foreach (['Hamburguesas', 'Bebidas', 'Postres'] as $index => $name) {
                $categories[$name] = ProductCategory::query()->updateOrCreate(['normalized_name' => LabelNormalizer::comparable($name)], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
            }
            $definitions = [
                ['Hamburguesas', 'Hamburguesa Clásica', [['Única', '18000.00', true]]],
                ['Hamburguesas', 'Hamburguesa Especial', [['Única', '25000.00', true]]],
                ['Bebidas', 'Coca-Cola', [['Personal', '5000.00', true], ['Litro', '9000.00', false]]],
                ['Bebidas', 'Limonada', [['Única', '7000.00', true]]],
                ['Postres', 'Torta Chocolate', [['Porción', '10000.00', true]]],
            ];
            foreach ($definitions as [$categoryName, $name, $variants]) {
                $product = Product::query()->updateOrCreate(['category_id' => $categories[$categoryName]->id, 'normalized_name' => LabelNormalizer::comparable($name)], ['name' => $name, 'description' => null, 'sort_order' => $categories[$categoryName]->products()->count() + 1, 'is_active' => true, 'is_available' => true, 'allow_notes' => true]);
                foreach ($variants as $sort => [$variantName, $price, $default]) {
                    $product->variants()->updateOrCreate(['normalized_name' => LabelNormalizer::comparable($variantName)], ['name' => $variantName, 'price' => $price, 'is_default' => $default, 'is_active' => true, 'is_available' => true, 'sort_order' => $sort + 1]);
                }
                foreach (ProductChannel::cases() as $channel) {
                    $product->channels()->updateOrCreate(['channel' => $channel->value], ['is_visible' => true]);
                }
            }
            $group = ModifierGroup::query()->updateOrCreate(['normalized_name' => LabelNormalizer::comparable('Adiciones')], ['name' => 'Adiciones', 'is_active' => true]);
            foreach ([['Tocineta', '4000.00'], ['Queso', '3000.00']] as $sort => [$name, $price]) {
                $group->options()->updateOrCreate(['normalized_name' => LabelNormalizer::comparable($name)], ['name' => $name, 'price_delta' => $price, 'sort_order' => $sort + 1, 'is_active' => true]);
            }
            $burger = Product::query()->where('normalized_name', LabelNormalizer::comparable('Hamburguesa Especial'))->firstOrFail();
            $burger->modifierAssignments()->updateOrCreate(['modifier_group_id' => $group->id], ['min_select' => 0, 'max_select' => 2, 'sort_order' => 1, 'is_active' => true]);
        });
    }
}
