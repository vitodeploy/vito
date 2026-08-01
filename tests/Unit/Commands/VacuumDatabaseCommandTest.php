<?php

use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('skips when connection is not sqlite', function () {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('mysql');
    $connection->shouldNotReceive('statement');

    DB::shouldReceive('connection')->andReturn($connection);

    $this->artisan('db:vacuum')
        ->expectsOutput('VACUUM is only supported on SQLite connections. Skipping.')
        ->assertSuccessful();
});

test('skips when database file is missing', function () {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('sqlite');
    $connection->shouldReceive('getDatabaseName')->andReturn('/path/that/does/not/exist.sqlite');
    $connection->shouldNotReceive('statement');

    DB::shouldReceive('connection')->andReturn($connection);

    $this->artisan('db:vacuum')
        ->expectsOutput('Could not locate the SQLite database file. Skipping.')
        ->assertSuccessful();
});

test('vacuums when disk space is sufficient', function () {
    $database = tempnam(sys_get_temp_dir(), 'vito-vacuum-test-');
    file_put_contents($database, 'x');

    try {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $connection->shouldReceive('getDatabaseName')->andReturn($database);
        $connection->shouldReceive('statement')->once()->with('VACUUM');

        DB::shouldReceive('connection')->andReturn($connection);

        $this->artisan('db:vacuum')
            ->expectsOutput('Vacuuming the database...')
            ->expectsOutput('Database vacuumed!')
            ->assertSuccessful();
    } finally {
        unlink($database);
    }
});
