<?php

use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('transform reindexes rows with gaps', function () {
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server->id,
    ]);

    DB::table('database_users')->where('id', $databaseUser->id)->update([
        'databases' => '{"0":"db_one","2":"db_three","5":"db_six"}',
    ]);

    vitoPestFeatureDatabaseUserDatabasesMigrationTestRunMigration();

    expect((string) DB::table('database_users')->where('id', $databaseUser->id)->value('databases'))
        ->toBe('["db_one","db_three","db_six"]');
});

test('transform leaves valid rows untouched', function () {
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server->id,
        'databases' => ['db_one', 'db_two'],
    ]);

    $before = (string) DB::table('database_users')->where('id', $databaseUser->id)->value('databases');

    vitoPestFeatureDatabaseUserDatabasesMigrationTestRunMigration();

    expect((string) DB::table('database_users')->where('id', $databaseUser->id)->value('databases'))->toBe($before);
});

test('manual recovery statement reindexes rows', function () {
    $databaseUser = DatabaseUser::factory()->create(['server_id' => $this->server->id]);
    $nullUser = DatabaseUser::factory()->create(['server_id' => $this->server->id]);
    $scalarUser = DatabaseUser::factory()->create(['server_id' => $this->server->id]);

    DB::table('database_users')->where('id', $databaseUser->id)->update([
        'databases' => '{"0":"db_one","2":"db_three","5":"db_six"}',
    ]);
    DB::table('database_users')->where('id', $nullUser->id)->update(['databases' => null]);
    DB::table('database_users')->where('id', $scalarUser->id)->update(['databases' => '"not-an-array"']);

    DatabaseUser::all()->filter(fn (DatabaseUser $u) => is_array($u->databases))->each(fn (DatabaseUser $u) => $u->update(['databases' => array_values($u->databases)]));

    $this->assertDatabaseHas('database_users', [
        'id' => $databaseUser->id,
        'databases' => '["db_one","db_three","db_six"]',
    ]);

    $this->assertDatabaseHas('database_users', [
        'id' => $nullUser->id,
        'databases' => null,
    ]);

    $this->assertDatabaseHas('database_users', [
        'id' => $scalarUser->id,
        'databases' => '"not-an-array"',
    ]);
});

function vitoPestFeatureDatabaseUserDatabasesMigrationTestRunMigration(): void
{
    $paths = glob(database_path('migrations/*_reindex_database_users_databases.php')) ?: [];
    test()->assertNotEmpty($paths, 'Database users databases migration not found.');

    $migration = require $paths[0];
    $migration->up();
}
