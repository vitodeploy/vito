<?php

namespace Tests\Feature;

use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkerEnvironmentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transform_converts_plaintext_map_rows_to_encrypted_list(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
        ]);

        DB::table('workers')->where('id', $worker->id)->update([
            'environment' => json_encode([
                'API_KEY' => 'secret-value',
                'NODE_ENV' => 'production',
            ]),
        ]);

        $this->runMigration();

        $environment = Worker::query()->findOrFail($worker->id)->environment;

        $this->assertEquals([
            ['key' => 'API_KEY', 'value' => 'secret-value', 'is_secret' => true],
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ], $environment);

        $raw = (string) DB::table('workers')->where('id', $worker->id)->value('environment');
        $this->assertStringNotContainsString('secret-value', $raw);
    }

    public function test_transform_re_encrypts_plaintext_empty_rows(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
        ]);

        DB::table('workers')->where('id', $worker->id)->update([
            'environment' => '[]',
        ]);

        $this->runMigration();

        $this->assertSame([], Worker::query()->findOrFail($worker->id)->environment);
    }

    public function test_transform_skips_already_encrypted_rows(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'environment' => [
                ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
            ],
        ]);

        $encrypted = (string) DB::table('workers')->where('id', $worker->id)->value('environment');

        $this->runMigration();

        $this->assertSame($encrypted, (string) DB::table('workers')->where('id', $worker->id)->value('environment'));
    }

    private function runMigration(): void
    {
        $paths = glob(database_path('migrations/*_add_worker_environment_support.php')) ?: [];
        $this->assertNotEmpty($paths, 'Worker environment migration not found.');

        $migration = require $paths[0];
        $migration->up();
    }
}
