<?php

namespace Tests\Feature;

use App\Domain\Areas\Models\Area;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreasApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_areas_with_tables_and_backend_counts(): void
    {
        $area = Area::factory()->create(['sort_order' => 1]);
        RestaurantTable::factory()->create(['area_id' => $area->id, 'is_active' => true]);
        RestaurantTable::factory()->create(['area_id' => $area->id, 'is_active' => false]);

        $this->getJson('/api/areas')->assertOk()->assertJson([
            'success' => true,
            'data' => ['areas' => [[
                'id' => $area->id,
                'tablesCount' => 2,
                'activeTablesCount' => 1,
                'inactiveTablesCount' => 1,
            ]]],
        ])->assertJsonStructure(['data' => ['areas' => [['tables']]]]);
    }

    public function test_creates_trimmed_area_and_rejects_equivalent_duplicate(): void
    {
        $response = $this->postJson('/api/areas', ['name' => '  Sala   VIP  ']);
        $response->assertCreated()->assertJson([
            'success' => true,
            'data' => ['area' => ['name' => 'Sala VIP', 'isActive' => true]],
        ]);

        $this->postJson('/api/areas', ['name' => ' sala vip '])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR']]);
        $this->assertDatabaseCount('areas', 1);
    }

    public function test_validates_area_name(): void
    {
        $this->postJson('/api/areas', ['name' => '   '])
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Escribe el nombre del área.');
    }

    public function test_updates_deactivates_and_reactivates_without_changing_id(): void
    {
        $area = Area::factory()->create(['name' => 'VIP', 'normalized_name' => 'vip']);

        $this->putJson('/api/areas/'.$area->id, ['name' => 'Terraza Premium', 'isActive' => false])
            ->assertOk()
            ->assertJsonPath('data.area.id', $area->id)
            ->assertJsonPath('data.area.isActive', false);
        $this->putJson('/api/areas/'.$area->id, ['isActive' => true])
            ->assertOk()
            ->assertJsonPath('data.area.isActive', true);
    }

    public function test_only_empty_area_can_be_deleted(): void
    {
        $occupied = Area::factory()->create();
        RestaurantTable::factory()->create(['area_id' => $occupied->id]);
        $this->deleteJson('/api/areas/'.$occupied->id)
            ->assertConflict()
            ->assertJsonPath('error.code', 'AREA_HAS_TABLES');

        $empty = Area::factory()->create();
        $this->deleteJson('/api/areas/'.$empty->id)->assertOk();
        $this->assertDatabaseMissing('areas', ['id' => $empty->id]);
    }

    public function test_reorders_all_areas_in_one_request(): void
    {
        $first = Area::factory()->create(['sort_order' => 1]);
        $second = Area::factory()->create(['sort_order' => 2]);

        $this->postJson('/api/areas/reorder', ['items' => [
            ['id' => $first->id, 'sortOrder' => 2],
            ['id' => $second->id, 'sortOrder' => 1],
        ]])->assertOk()->assertJsonPath('data.areas.0.id', $second->id);
    }
}
