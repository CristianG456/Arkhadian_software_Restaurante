<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\ModifierOption;
use App\Domain\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogModifiersManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_can_be_renamed_without_changing_its_identity_or_relations(): void
    {
        $group = ModifierGroup::factory()->create(['name' => 'Papa Francesa', 'normalized_name' => 'papa francesa']);
        $option = ModifierOption::factory()->create(['modifier_group_id' => $group->id]);
        $product = Product::factory()->create();
        $assignment = $product->modifierAssignments()->create(['modifier_group_id' => $group->id, 'min_select' => 0, 'max_select' => 1, 'sort_order' => 1, 'is_active' => true]);

        $this->putJson('/api/catalog/modifier-groups/'.$group->id, ['name' => ' Término de carne '])
            ->assertOk()->assertJsonPath('data.modifierGroup.id', $group->id)->assertJsonPath('data.modifierGroup.name', 'Término de carne');

        $this->assertSame($group->id, $option->refresh()->modifier_group_id);
        $this->assertSame($group->id, $assignment->refresh()->modifier_group_id);
    }

    public function test_equivalent_group_name_is_rejected(): void
    {
        ModifierGroup::factory()->create(['name' => 'Término de carne', 'normalized_name' => 'termino de carne']);
        $other = ModifierGroup::factory()->create();
        $this->putJson('/api/catalog/modifier-groups/'.$other->id, ['name' => ' TERMINO DE CARNE '])
            ->assertStatus(409)->assertJsonPath('error.code', 'MODIFIER_GROUP_ALREADY_EXISTS');
    }

    public function test_unused_group_and_its_options_are_deleted_together(): void
    {
        $group = ModifierGroup::factory()->create();
        $options = ModifierOption::factory()->count(2)->create(['modifier_group_id' => $group->id]);
        $this->deleteJson('/api/catalog/modifier-groups/'.$group->id)->assertOk();
        $this->assertDatabaseMissing('modifier_groups', ['id' => $group->id]);
        foreach ($options as $option) {
            $this->assertDatabaseMissing('modifier_options', ['id' => $option->id]);
        }
    }

    public function test_assigned_group_cannot_be_deleted_but_can_be_deactivated_and_reactivated(): void
    {
        $group = ModifierGroup::factory()->create();
        $product = Product::factory()->create();
        $product->modifierAssignments()->create(['modifier_group_id' => $group->id, 'min_select' => 0, 'max_select' => 1, 'sort_order' => 1, 'is_active' => true]);
        $this->deleteJson('/api/catalog/modifier-groups/'.$group->id)->assertStatus(409)->assertJsonPath('error.code', 'MODIFIER_GROUP_IN_USE')->assertJsonPath('error.message', 'No puedes eliminar este grupo porque está siendo utilizado por 1 producto.');
        $this->putJson('/api/catalog/modifier-groups/'.$group->id, ['isActive' => false])->assertOk()->assertJsonPath('data.modifierGroup.isActive', false);
        $this->putJson('/api/catalog/modifier-groups/'.$group->id, ['isActive' => true])->assertOk()->assertJsonPath('data.modifierGroup.isActive', true);
    }

    public function test_inactive_group_cannot_be_assigned_to_a_new_product(): void
    {
        $group = ModifierGroup::factory()->create(['is_active' => false]);
        ModifierOption::factory()->create(['modifier_group_id' => $group->id]);
        $product = Product::factory()->create();
        $this->postJson('/api/catalog/products/'.$product->id.'/modifier-groups', ['modifierGroupId' => $group->id, 'minSelect' => 0, 'maxSelect' => 1])
            ->assertStatus(409)->assertJsonPath('error.code', 'MODIFIER_GROUP_INACTIVE');
    }

    public function test_option_can_be_renamed_and_repriced_without_changing_identity(): void
    {
        $option = ModifierOption::factory()->create(['name' => 'Bien cocida', 'normalized_name' => 'bien cocida', 'price_delta' => '0.00']);
        $this->putJson('/api/catalog/modifier-groups/'.$option->modifier_group_id.'/options/'.$option->id, ['name' => 'Bien asada', 'priceDelta' => '3500.50'])
            ->assertOk()->assertJsonPath('data.option.id', $option->id)->assertJsonPath('data.option.name', 'Bien asada')->assertJsonPath('data.option.priceDelta', '3500.50');
    }

    public function test_safe_option_can_be_deleted(): void
    {
        $option = ModifierOption::factory()->create();
        $this->deleteJson('/api/catalog/modifier-groups/'.$option->modifier_group_id.'/options/'.$option->id)->assertOk();
        $this->assertDatabaseMissing('modifier_options', ['id' => $option->id]);
    }

    public function test_required_option_cannot_be_deleted_or_deactivated(): void
    {
        $group = ModifierGroup::factory()->create();
        $options = ModifierOption::factory()->count(2)->create(['modifier_group_id' => $group->id, 'is_active' => true]);
        $product = Product::factory()->create();
        $product->modifierAssignments()->create(['modifier_group_id' => $group->id, 'min_select' => 2, 'max_select' => 2, 'sort_order' => 1, 'is_active' => true]);

        $url = '/api/catalog/modifier-groups/'.$group->id.'/options/'.$options[1]->id;
        $this->deleteJson($url)->assertStatus(409)->assertJsonPath('error.code', 'MODIFIER_OPTION_REQUIRED_BY_CONFIGURATION');
        $this->putJson($url, ['isActive' => false])->assertStatus(409)->assertJsonPath('error.code', 'MODIFIER_MINIMUM_BROKEN');
        $this->assertTrue($options[1]->refresh()->is_active);
    }
}
