<?php

namespace Tests\Feature;

use App\Domain\Areas\Models\Area;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTablesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_and_creates_table_in_area(): void
    {
        $area = Area::factory()->create();
        RestaurantTable::factory()->create([
            'area_id' => $area->id,
            'label' => '1',
            'normalized_label' => '1',
        ]);

        $this->getJson('/api/areas/'.$area->id.'/tables')
            ->assertOk()->assertJsonCount(1, 'data.tables');
        $this->postJson('/api/areas/'.$area->id.'/tables', ['label' => 'Mesa A1'])
            ->assertCreated()
            ->assertJsonPath('data.table.label', 'A1')
            ->assertJsonPath('data.table.displayName', 'Mesa A1');
    }

    public function test_rejects_duplicate_in_same_area_but_allows_it_in_another(): void
    {
        $vip = Area::factory()->create();
        $salon = Area::factory()->create();
        $this->postJson('/api/areas/'.$vip->id.'/tables', ['label' => '4'])->assertCreated();
        $this->postJson('/api/areas/'.$vip->id.'/tables', ['label' => '  MESA 4 '])
            ->assertConflict()->assertJsonPath('error.code', 'TABLE_LABEL_DUPLICATE');
        $this->postJson('/api/areas/'.$salon->id.'/tables', ['label' => '4'])->assertCreated();
    }

    public function test_edits_deactivates_reactivates_and_moves_table(): void
    {
        $source = Area::factory()->create();
        $target = Area::factory()->create();
        $table = RestaurantTable::factory()->create(['area_id' => $source->id]);

        $this->putJson('/api/tables/'.$table->id, [
            'label' => 'A2', 'areaId' => $target->id, 'isActive' => false,
        ])->assertOk()
            ->assertJsonPath('data.table.areaId', $target->id)
            ->assertJsonPath('data.table.isActive', false);
        $this->putJson('/api/tables/'.$table->id, ['isActive' => true])
            ->assertOk()->assertJsonPath('data.table.isActive', true);
    }

    public function test_move_rejects_duplicate_in_target_area(): void
    {
        $source = Area::factory()->create();
        $target = Area::factory()->create();
        $moving = RestaurantTable::factory()->create([
            'area_id' => $source->id, 'label' => '4', 'normalized_label' => '4',
        ]);
        RestaurantTable::factory()->create([
            'area_id' => $target->id, 'label' => '4', 'normalized_label' => '4',
        ]);

        $this->putJson('/api/tables/'.$moving->id, ['areaId' => $target->id])
            ->assertConflict()->assertJsonPath('error.code', 'TABLE_LABEL_DUPLICATE');
    }

    public function test_bulk_create_reports_existing_tables_without_overwriting_them(): void
    {
        $area = Area::factory()->create();
        RestaurantTable::factory()->create([
            'area_id' => $area->id, 'label' => '3', 'normalized_label' => '3',
        ]);

        $this->postJson('/api/areas/'.$area->id.'/tables/bulk', ['from' => 1, 'to' => 5])
            ->assertCreated()
            ->assertJsonPath('data.createdCount', 4)
            ->assertJsonPath('data.skippedCount', 1)
            ->assertJsonPath('data.skippedExisting.0.label', '3');
        $this->assertSame(5, RestaurantTable::query()->where('area_id', $area->id)->count());
    }

    public function test_bulk_create_enforces_safe_limit(): void
    {
        $area = Area::factory()->create();
        $this->postJson('/api/areas/'.$area->id.'/tables/bulk', ['from' => 1, 'to' => 101])
            ->assertUnprocessable()->assertJsonPath('error.code', 'TABLE_BULK_LIMIT');
    }

    public function test_reorders_and_deletes_tables(): void
    {
        $area = Area::factory()->create();
        $first = RestaurantTable::factory()->create(['area_id' => $area->id, 'sort_order' => 1]);
        $second = RestaurantTable::factory()->create(['area_id' => $area->id, 'sort_order' => 2]);

        $this->postJson('/api/areas/'.$area->id.'/tables/reorder', ['items' => [
            ['id' => $first->id, 'sortOrder' => 2],
            ['id' => $second->id, 'sortOrder' => 1],
        ]])->assertOk()->assertJsonPath('data.tables.0.id', $second->id);

        $this->deleteJson('/api/tables/'.$first->id)->assertOk();
        $this->assertDatabaseMissing('restaurant_tables', ['id' => $first->id]);
    }
}
