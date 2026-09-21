<?php

use App\DTOs\ServiceLog;
use App\Services\Database\Clickhouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $database = $this->server->database();
    $database->name = 'clickhouse';
    $database->version = '24.8';
    $database->save();

    /** @var Clickhouse $handler */
    $handler = $database->handler();
    $this->clickhouse = $handler;
});

test('id returns clickhouse', function () {
    expect(Clickhouse::id())->toBe('clickhouse')
        ->and(Clickhouse::type())->toBe('database');
});

test('unit returns clickhouse-server', function () {
    expect($this->clickhouse->unit())->toBe('clickhouse-server');
});

test('version command is defined', function () {
    expect($this->clickhouse->versionCommand())->toContain('clickhouse-client --version');
});

test('networking configuration', function () {
    expect($this->clickhouse->networkingPort())->toBe(9000)
        ->and($this->clickhouse->usesHost())->toBeFalse()
        ->and($this->clickhouse->networkingProbeRequiresRunning())->toBeTrue()
        ->and($this->clickhouse->networkingProbeCommand())->toContain('system.server_settings')
        ->and($this->clickhouse->parseNetworkingProbe('0.0.0.0'))->toBeTrue()
        ->and($this->clickhouse->parseNetworkingProbe('127.0.0.1'))->toBeFalse()
        ->and($this->clickhouse->parseNetworkingProbe(''))->toBeNull();
});

test('logs returns service journal', function () {
    $logs = $this->clickhouse->logs();

    expect($logs)->toHaveCount(1)
        ->and($logs[0])->toBeInstanceOf(ServiceLog::class)
        ->and($logs[0]->key)->toBe('clickhouse:journal')
        ->and($logs[0]->serviceLabel)->toBe('ClickHouse')
        ->and($logs[0]->target)->toBe('clickhouse-server.service');
});

test('clickhouse is registered in service configs', function () {
    $services = config('service.services');

    expect($services)->toHaveKey('clickhouse')
        ->and($services['clickhouse']['type'])->toBe('database')
        ->and($services['clickhouse']['versions'])->toContain('24.8', '24.3', '23.8');
});

test('default charset is UTF8', function () {
    $ref = new ReflectionProperty(Clickhouse::class, 'defaultCharset');
    expect($ref->getValue($this->clickhouse))->toBe('UTF8');
});

test('backup script renders and escapes database identifiers', function (string $database) {
    $rendered = view('ssh.services.database.clickhouse.backup', [
        'database' => $database,
        'path' => '/tmp/backup.tar.gz',
    ])->render();

    expect($rendered)->toContain('set -euo pipefail')
        ->and($rendered)->toContain('trap cleanup EXIT')
        ->and($rendered)->toContain('SQ=$(printf "\x27")')
        ->and($rendered)->toContain('DB_NAME="${1//\\\\/\\\\\\\\}"')
        ->and($rendered)->toContain('DB_NAME="${DB_NAME//\\`/\\`\\`}"')
        ->and($rendered)->toContain('BACKUP DATABASE \\`${DB_NAME}\\` TO Disk(${SQ}backups${SQ}')
        ->and($rendered)->toContain('sudo chown "$(id -un):$(id -gn)" "$2"')
        ->and($rendered)->toContain("echo 'VITO_SSH_ERROR' && exit 1")
        ->and($rendered)->toContain(escapeshellarg($database));
})->with([
    'analytics-db',
    'analytics-db`test',
    'analytics\\db',
    'db_name_123',
]);

test('restore script renders and escapes database identifiers', function (string $database) {
    $rendered = view('ssh.services.database.clickhouse.restore', [
        'database' => $database,
        'path' => '/tmp/backup.tar.gz',
    ])->render();

    expect($rendered)->toContain('set -euo pipefail')
        ->and($rendered)->toContain('trap cleanup EXIT')
        ->and($rendered)->toContain('SQ=$(printf "\x27")')
        ->and($rendered)->toContain('DB_NAME="${1//\\\\/\\\\\\\\}"')
        ->and($rendered)->toContain('DB_NAME="${DB_NAME//\\`/\\`\\`}"')
        ->and($rendered)->toContain('STAGING_DB="_vito_restore_$(date +%s%N)"')
        ->and($rendered)->toContain('DROP DATABASE IF EXISTS \\`${STAGING_DB}\\` SYNC;')
        ->and($rendered)->toContain('RESTORE DATABASE \\`${DB_NAME}\\` AS \\`${STAGING_DB}\\` FROM Disk(${SQ}backups${SQ}')
        ->and($rendered)->toContain('DROP DATABASE IF EXISTS \\`${DB_NAME}\\` SYNC;')
        ->and($rendered)->toContain('RENAME DATABASE \\`${STAGING_DB}\\` TO \\`${DB_NAME}\\`;')
        ->and($rendered)->toContain("echo 'VITO_SSH_ERROR' && exit 1")
        ->and($rendered)->toContain(escapeshellarg($database));
})->with([
    'analytics-db',
    'analytics-db`test',
    'analytics\\db',
    'db_name_123',
]);



