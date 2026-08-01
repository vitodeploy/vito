<?php

namespace Tests\Feature;

use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseUserDatabasesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transform_reindexes_rows_with_gaps(): void
    {
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server->id,
        ]);

        DB::table('database_users')->where('id', $databaseUser->id)->update([
            'databases' => '{"0":"db_one","2":"db_three","5":"db_six"}',
        ]);

        $this->runMigration();

        $this->assertSame(
            '["db_one","db_three","db_six"]',
            (string) DB::table('database_users')->where('id', $databaseUser->id)->value('databases')
        );
    }

    public function test_transform_leaves_valid_rows_untouched(): void
    {
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server->id,
            'databases' => ['db_one', 'db_two'],
        ]);

        $before = (string) DB::table('database_users')->where('id', $databaseUser->id)->value('databases');

        $this->runMigration();

        $this->assertSame($before, (string) DB::table('database_users')->where('id', $databaseUser->id)->value('databases'));
    }

    private function runMigration(): void
    {
        $paths = glob(database_path('migrations/*_reindex_database_users_databases.php')) ?: [];
        $this->assertNotEmpty($paths, 'Database users databases migration not found.');

        $migration = require $paths[0];
        $migration->up();
    }
}
