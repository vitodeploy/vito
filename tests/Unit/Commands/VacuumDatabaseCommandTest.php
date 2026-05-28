<?php

namespace Tests\Unit\Commands;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class VacuumDatabaseCommandTest extends TestCase
{
    public function test_skips_when_connection_is_not_sqlite(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldNotReceive('statement');

        DB::shouldReceive('connection')->andReturn($connection);

        $this->artisan('db:vacuum')
            ->expectsOutput('VACUUM is only supported on SQLite connections. Skipping.')
            ->assertSuccessful();
    }

    public function test_skips_when_database_file_is_missing(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $connection->shouldReceive('getDatabaseName')->andReturn('/path/that/does/not/exist.sqlite');
        $connection->shouldNotReceive('statement');

        DB::shouldReceive('connection')->andReturn($connection);

        $this->artisan('db:vacuum')
            ->expectsOutput('Could not locate the SQLite database file. Skipping.')
            ->assertSuccessful();
    }

    public function test_vacuums_when_disk_space_is_sufficient(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'vito-vacuum-test-');
        file_put_contents($database, 'x');

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $connection->shouldReceive('getDatabaseName')->andReturn($database);
        $connection->shouldReceive('statement')->once()->with('VACUUM');

        DB::shouldReceive('connection')->andReturn($connection);

        $this->artisan('db:vacuum')
            ->expectsOutput('Vacuuming the database...')
            ->expectsOutput('Database vacuumed!')
            ->assertSuccessful();

        unlink($database);
    }
}
