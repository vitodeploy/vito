<?php

use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('transform converts plaintext map rows to an encrypted list of secrets', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
    ]);

    DB::table('workers')->where('id', $worker->id)->update([
        'environment' => json_encode([
            'API_KEY' => 'secret-value',
            'DATABASE_URL' => 'postgres://app:db-secret@db/app',
            'NODE_ENV' => 'production',
        ]),
    ]);

    vitoPestFeatureWorkerEnvironmentMigrationTestRunMigration();

    $environment = Worker::query()->findOrFail($worker->id)->environment;

    expect($environment)->toEqual([
        ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
        ['key' => 'DATABASE_URL', 'value' => 'postgres://app:db-secret@db/app', 'is_secret' => true],
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => true],
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

test('repair marks migrated credential urls secret and keeps other choices', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'environment' => [
            ['key' => 'REDIS_URL', 'value' => 'redis://cache:6379', 'is_secret' => false],
            ['key' => 'UPSTREAM', 'value' => 'https://user:pass@example.com/hook', 'is_secret' => false],
            ['key' => 'DB_PWD', 'value' => 'plain', 'is_secret' => false],
            ['key' => 'HOOK', 'value' => 'https://token@example.com', 'is_secret' => false],
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ],
    ]);
    $untouched = Worker::factory()->create([
        'server_id' => $this->server->id,
        'environment' => [['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false]],
    ]);
    $before = (string) DB::table('workers')->where('id', $untouched->id)->value('environment');
    DB::table('workers')->insert([...collect($untouched->getAttributes())->except(['id', 'environment'])->all(), 'environment' => 'not-encrypted']);

    vitoPestFeatureWorkerEnvironmentMigrationTestRunMigration('mark_worker_environment_credential_urls_secret');

    expect(Worker::query()->findOrFail($worker->id)->environment)->toBe([
        ['key' => 'REDIS_URL', 'value' => 'redis://cache:6379', 'is_secret' => true],
        ['key' => 'UPSTREAM', 'value' => 'https://user:pass@example.com/hook', 'is_secret' => true],
        ['key' => 'DB_PWD', 'value' => 'plain', 'is_secret' => true],
        ['key' => 'HOOK', 'value' => 'https://token@example.com', 'is_secret' => true],
        ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
    ])->and((string) DB::table('workers')->where('id', $untouched->id)->value('environment'))->toBe($before);
});

function vitoPestFeatureWorkerEnvironmentMigrationTestRunMigration(string $name = 'add_worker_environment_support'): void
{
    $paths = glob(database_path('migrations/*_'.$name.'.php')) ?: [];
    expect($paths)->not->toBeEmpty('Worker environment migration not found.');

    $migration = require $paths[0];
    $migration->up();
}
