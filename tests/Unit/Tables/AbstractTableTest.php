<?php

namespace Tests\Unit\Tables;

use App\Enums\ServerStatus;
use App\Models\Server;
use App\Tables\ServerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbstractTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_inertia_returns_correct_shape(): void
    {
        $this->actingAs($this->user);

        Server::factory()->count(3)->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
        ]);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();

        $this->assertArrayHasKey('columns', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('searchable', $result);

        $this->assertArrayHasKey('links', $result);
        $this->assertArrayHasKey('meta', $result);

        $this->assertArrayHasKey('first', $result['links']);
        $this->assertArrayHasKey('last', $result['links']);
        $this->assertArrayHasKey('prev', $result['links']);
        $this->assertArrayHasKey('next', $result['links']);

        $this->assertArrayHasKey('current_page', $result['meta']);
        $this->assertArrayHasKey('per_page', $result['meta']);
        $this->assertArrayHasKey('path', $result['meta']);
    }

    public function test_columns_are_serialized(): void
    {
        $this->actingAs($this->user);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();

        $columns = $result['columns'];
        $this->assertNotEmpty($columns);

        $firstColumn = $columns[0];
        $this->assertArrayHasKey('name', $firstColumn);
        $this->assertArrayHasKey('header', $firstColumn);
        $this->assertArrayHasKey('sortable', $firstColumn);
        $this->assertArrayHasKey('sort_key', $firstColumn);
        $this->assertArrayHasKey('hidden', $firstColumn);
        $this->assertArrayHasKey('displays', $firstColumn);
    }

    public function test_rows_contain_column_fields(): void
    {
        $this->actingAs($this->user);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'name' => 'test-server',
            'ip' => '10.0.0.1',
            'status' => ServerStatus::READY,
        ]);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();
        $rows = $result['data'];

        $row = collect($rows)->firstWhere('name', 'test-server');
        $this->assertNotNull($row);
        $this->assertEquals('test-server', $row['name']);
        $this->assertEquals('10.0.0.1', $row['ip']);
        $this->assertArrayHasKey('status', $row);
        $this->assertArrayHasKey('created_at', $row);
    }

    public function test_enum_column_resolves_text_and_color(): void
    {
        $this->actingAs($this->user);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();
        $rows = $result['data'];

        $row = collect($rows)->first(fn ($r) => ($r['_status_enum_color'] ?? null) !== null);
        $this->assertNotNull($row);
        $this->assertEquals(ServerStatus::READY->getText(), $row['status']);
        $this->assertEquals(ServerStatus::READY->getColor(), $row['_status_enum_color']);
    }

    public function test_search_filters_results(): void
    {
        $this->actingAs($this->user);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'name' => 'alpha-server',
        ]);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'name' => 'beta-server',
        ]);

        request()->merge(['search' => 'alpha']);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();
        $rows = $result['data'];

        $this->assertCount(1, $rows);
        $this->assertEquals('alpha-server', $rows[0]['name']);
    }

    public function test_sorting_orders_results(): void
    {
        $this->actingAs($this->user);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'name' => 'charlie',
        ]);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'name' => 'alpha',
        ]);

        request()->merge(['sort_by' => 'name', 'sort_dir' => 'asc']);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();
        $rows = $result['data'];
        $names = array_column($rows, 'name');
        $sorted = $names;
        sort($sorted);

        $this->assertEquals($sorted, $names);
    }

    public function test_sorting_reorders_pre_ordered_query(): void
    {
        $this->actingAs($this->user);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'name' => 'zebra',
        ]);

        Server::factory()->create([
            'project_id' => $this->user->currentProject->id,
            'user_id' => $this->user->id,
            'name' => 'alpha',
        ]);

        request()->merge(['sort_by' => 'name', 'sort_dir' => 'asc']);

        $query = $this->user->currentProject->servers()->latest();
        $result = ServerTable::make($query)->toInertia();
        $rows = $result['data'];
        $names = array_column($rows, 'name');
        $sorted = $names;
        sort($sorted);

        $this->assertEquals($sorted, $names);
    }

    public function test_invalid_sort_field_falls_back_to_default(): void
    {
        $this->actingAs($this->user);

        request()->merge(['sort_by' => 'nonexistent_column', 'sort_dir' => 'asc']);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();

        $this->assertNotEmpty($result['data']);
    }

    public function test_hidden_columns_are_marked(): void
    {
        $this->actingAs($this->user);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();
        $columns = $result['columns'];

        $visibleColumns = array_filter($columns, fn ($col) => $col['hidden'] === false);
        $this->assertNotEmpty($visibleColumns);

        $hiddenColumns = array_filter($columns, fn ($col) => $col['hidden'] === true);
        $this->assertEmpty($hiddenColumns, 'ServerTable uses enum() so no explicit hidden columns exist');
    }

    public function test_searchable_flag_reflects_configuration(): void
    {
        $this->actingAs($this->user);

        $result = ServerTable::make($this->user->currentProject->servers())->toInertia();

        $this->assertTrue($result['searchable']);
    }
}
