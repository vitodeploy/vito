<?php

use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('transform converts plaintext map rows to encrypted list', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    DB::table('workers')->where('id', $worker->id)->update([
        'environment' => json_encode([
            'API_KEY' => 'secret-value',
            'NODE_ENV' => 'production',
        ]),
    ]);

    vitoPestFeatureWorkerEnvironmentMigrationTestRunMigration();

    $environment = Worker::query()->findOrFail($worker->id)->environment;

    expect($environment)->toEqual([
        ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ]);

    $raw = (string) DB::table('workers')->where('id', $worker->id)->value('environment');
    $this->assertStringNotContainsString('secret-value', $raw);
});

test('transform re encrypts plaintext empty rows', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    DB::table('workers')->where('id', $worker->id)->update([
        'environment' => '[]',
    ]);

    vitoPestFeatureWorkerEnvironmentMigrationTestRunMigration();

    expect(Worker::query()->findOrFail($worker->id)->environment)->toBe([]);
});

test('transform skips already encrypted rows', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'environment' => [
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
    ]);

    $encrypted = (string) DB::table('workers')->where('id', $worker->id)->value('environment');

    vitoPestFeatureWorkerEnvironmentMigrationTestRunMigration();

    expect((string) DB::table('workers')->where('id', $worker->id)->value('environment'))->toBe($encrypted);
});

function vitoPestFeatureWorkerEnvironmentMigrationTestRunMigration(): void
{
    $paths = glob(database_path('migrations/*_add_worker_environment_support.php')) ?: [];
    expect($paths)->not->toBeEmpty('Worker environment migration not found.');

    $migration = require $paths[0];
    $migration->up();
}
