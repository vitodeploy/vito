<?php

use App\Enums\ServiceStatus;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Testing\SSHFake;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('enable mysql networking', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');

    SSH::fake("Active: active\n0.0.0.0\nmysqlx_bind_address\t0.0.0.0");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    expect($service->status)->toEqual(ServiceStatus::READY);
    expect($service->secret)->toBeNull();

    SSH::assertExecutedContains('sudo mkdir -p /etc/mysql/mysql.conf.d');
    SSH::assertExecutedContains('sudo cp /etc/mysql/mysql.conf.d/zz-vito-networking.cnf /etc/mysql/mysql.conf.d/zz-vito-networking.cnf.vito.bak');
    SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'0.0.0.0\' | sudo tee /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
    SSH::assertExecutedContains('printf \'loose-mysqlx-bind-address = %s\n\' \'0.0.0.0\' | sudo tee -a /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
    SSH::assertExecutedContains('sudo systemctl restart mysql');
    SSH::assertExecutedContains('sudo mysql -N -e "SELECT @@bind_address"');
    SSH::assertExecutedContains('sudo mysql -N -e "SHOW VARIABLES LIKE \'mysqlx_bind_address\'"');
    SSH::assertNotExecutedContains('.vito.bak /etc/mysql/mysql.conf.d/zz-vito-networking.cnf', 'The drop-in must not be rolled back.');
});

test('disable mysql networking', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');
    $service->type_data = ['networking' => true];
    $service->save();

    SSH::fake("Active: active\n127.0.0.1\nmysqlx_bind_address\t127.0.0.1");

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeFalse();
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'127.0.0.1\' | sudo tee /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
    SSH::assertExecutedContains('printf \'loose-mysqlx-bind-address = %s\n\' \'127.0.0.1\' | sudo tee -a /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
    SSH::assertExecutedContains('sudo systemctl restart mysql');
    SSH::assertNotExecutedContains('0.0.0.0');
});

test('enable mysql networking tolerates a disabled x plugin', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');

    SSH::fake("Active: active\n0.0.0.0");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains('sudo mysql -N -e "SHOW VARIABLES LIKE \'mysqlx_bind_address\'"');
    SSH::assertNotExecutedContains('SELECT @@mysqlx_bind_address');
});

test('enable mysql networking fails when the x plugin stays local', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');

    SSH::fake("Active: active\n0.0.0.0\nmysqlx_bind_address\t127.0.0.1");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeFalse();
    expect($service->type_data['networking_failed'])->toBeTrue();

    SSH::assertExecutedContains('sudo rm -f /etc/mysql/mysql.conf.d/zz-vito-networking.cnf');
});

test('enable mariadb networking does not manage the x plugin', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mariadb', '11.4');

    SSH::fake("Active: active\n0.0.0.0");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains('sudo mkdir -p /etc/mysql/mariadb.conf.d');
    SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'0.0.0.0\' | sudo tee /etc/mysql/mariadb.conf.d/zz-vito-networking.cnf > /dev/null');
    SSH::assertExecutedContains('sudo systemctl restart mariadb');
    SSH::assertExecutedContains('sudo mariadb -N -e "SELECT @@bind_address"');
    SSH::assertNotExecutedContains('loose-mysqlx-bind-address', 'MariaDB has no X plugin.');
    SSH::assertNotExecutedContains('mysqlx_bind_address', 'MariaDB has no X plugin.');
    SSH::assertNotExecutedContains('sudo mysql -N', 'MariaDB must use the mariadb client.');
});

test('disable mariadb networking', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mariadb', '11.4');
    $service->type_data = ['networking' => true];
    $service->save();

    SSH::fake("Active: active\n127.0.0.1");

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    expect($service->refresh()->type_data['networking'])->toBeFalse();

    SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'127.0.0.1\' | sudo tee /etc/mysql/mariadb.conf.d/zz-vito-networking.cnf > /dev/null');
    SSH::assertNotExecutedContains('loose-mysqlx-bind-address');
});

test('enable postgresql networking', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('postgresql', '16');

    SSH::fake("Active: active\n0.0.0.0");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/postgresql.conf /etc/postgresql/16/main/postgresql.conf.vito.bak');
    SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/pg_hba.conf /etc/postgresql/16/main/pg_hba.conf.vito.bak');
    SSH::assertExecutedContains('sudo grep -Eq \'^[[:space:]]*include_dir[[:space:]]*=\' /etc/postgresql/16/main/postgresql.conf || printf "\ninclude_dir = \'conf.d\'\n" | sudo tee -a /etc/postgresql/16/main/postgresql.conf > /dev/null');
    SSH::assertExecutedContains('sudo mkdir -p /etc/postgresql/16/main/conf.d');
    SSH::assertExecutedContains('printf "listen_addresses = \'%s\'\n" \'0.0.0.0\' | sudo tee /etc/postgresql/16/main/conf.d/zz-vito-networking.conf > /dev/null');
    SSH::assertExecutedContains('sudo sed -i \'/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d\' /etc/postgresql/16/main/pg_hba.conf');
    SSH::assertExecutedContains('host all postgres 0.0.0.0/0 reject\nhost all all 0.0.0.0/0 scram-sha-256');
    SSH::assertExecutedContains('sudo systemctl restart postgresql');
    SSH::assertExecutedContains('sudo -u postgres psql -tAc "SHOW listen_addresses"');
    SSH::assertNotExecutedContains('ALTER SYSTEM', 'PostgreSQL networking must be file based.');
});

test('disable postgresql networking', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('postgresql', '16');
    $service->type_data = ['networking' => true];
    $service->save();

    SSH::fake("Active: active\nlocalhost");

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeFalse();
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains('sudo grep -Eq \'^[[:space:]]*include_dir[[:space:]]*=\' /etc/postgresql/16/main/postgresql.conf || printf "\ninclude_dir = \'conf.d\'\n" | sudo tee -a /etc/postgresql/16/main/postgresql.conf > /dev/null');
    SSH::assertExecutedContains('printf "listen_addresses = \'%s\'\n" \'localhost\' | sudo tee /etc/postgresql/16/main/conf.d/zz-vito-networking.conf > /dev/null');
    SSH::assertExecutedContains('sudo sed -i \'/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d\' /etc/postgresql/16/main/pg_hba.conf');
    SSH::assertExecutedContains('sudo systemctl restart postgresql');
    SSH::assertNotExecutedContains('scram-sha-256', 'The managed pg_hba block must not be written back on disable.');
    SSH::assertNotExecutedContains('sudo rm -f /etc/postgresql/16/main/conf.d/zz-vito-networking.conf', 'Disable rewrites the drop-in instead of deleting it.');
});

test('enable networking skips the restart when the service is not running', function (string $name, string $version) {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService($name, $version);
    $service->status = ServiceStatus::STOPPED;
    $service->save();

    SSH::fake();

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    expect($service->type_data['networking_effective'])->toBeTrue();
    expect($service->type_data['networking_checked_at'] ?? null)->toBeNull('A toggle that never restarted or verified must not stamp an observation timestamp.');
    expect($service->status)->toEqual(ServiceStatus::STOPPED);

    SSH::assertNotExecutedContains('systemctl restart');
    SSH::assertNotExecutedContains('SELECT @@bind_address');
    SSH::assertNotExecutedContains('SHOW listen_addresses');
})->with('databases');

test('failed mysql enable rolls back the drop in and marks the service as failed', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');

    SSH::fake("Active: active\n127.0.0.1");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeFalse();
    expect($service->type_data['networking_failed'])->toBeTrue();

    SSH::assertExecutedContains('sudo rm -f /etc/mysql/mysql.conf.d/zz-vito-networking.cnf');
    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'enable-networking-failed',
    ]);
});

test('failed mysql enable restores a previous drop in instead of deleting it', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');

    SSH::fake("Active: active\n127.0.0.1\nmysqlx_bind_address\t127.0.0.1");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);

    SSH::assertExecutedContains('sudo cp /etc/mysql/mysql.conf.d/zz-vito-networking.cnf.vito.bak /etc/mysql/mysql.conf.d/zz-vito-networking.cnf');
});

test('failed mysql disable does not roll back', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');
    $service->type_data = ['networking' => true];
    $service->save();

    SSH::fake("Active: active\n0.0.0.0");

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeTrue();

    SSH::assertNotExecutedContains('.vito.bak /etc/mysql/mysql.conf.d/zz-vito-networking.cnf', 'The drop-in must not be rolled back.');
});

test('failed postgresql enable restores both backups', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('postgresql', '16');

    SSH::fake("Active: active\nlocalhost");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeFalse();
    expect($service->type_data['networking_failed'])->toBeTrue();

    SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/postgresql.conf.vito.bak /etc/postgresql/16/main/postgresql.conf');
    SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/pg_hba.conf.vito.bak /etc/postgresql/16/main/pg_hba.conf');
    SSH::assertExecutedContains('sudo rm -f /etc/postgresql/16/main/conf.d/zz-vito-networking.conf');
});

test('failed postgresql config write rolls back the drop in', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('postgresql', '16');

    SSH::swap(new class extends SSHFake
    {
        public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null, int $timeout = 0): string
        {
            if (str((string) $command)->contains('sudo tee /etc/postgresql/16/main/conf.d/zz-vito-networking.conf')) {
                throw new SSHCommandError('Connection failed');
            }

            return parent::exec($command, $log, $siteId, $stream, $streamCallback, $timeout);
        }
    });

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeFalse();
    expect($service->type_data['networking_failed'])->toBeTrue();

    SSH::assertExecutedContains('sudo rm -f /etc/postgresql/16/main/conf.d/zz-vito-networking.conf');
    SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/postgresql.conf.vito.bak /etc/postgresql/16/main/postgresql.conf');
});

test('failed postgresql disable never restores the open state', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('postgresql', '16');
    $service->type_data = ['networking' => true];
    $service->save();

    SSH::fake("Active: active\n0.0.0.0");

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeTrue();

    SSH::assertNotExecutedContains('.vito.bak /etc/postgresql/16/main/', 'A failed disable must never restore the open state.');
});

test('mysql networking details', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('mysql', '8.4');
    $service->type_data = [
        'networking_effective' => true,
        'networking_checked_at' => '2026-07-26T12:00:00+00:00',
    ];
    $service->save();

    SSH::fake();

    $response = $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson([
            'supported' => true,
            'pending' => false,
            'enabled' => false,
            'effective' => true,
            'checked_at' => '2026-07-26T12:00:00+00:00',
            'port' => 3306,
            'requires_remote_users' => true,
        ]);

    $this->assertArrayNotHasKey('secret', $response->json());

    SSH::assertNotExecutedContains('SELECT @@bind_address');
});

test('postgresql networking details', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService('postgresql', '16');
    $service->type_data = [
        'networking' => true,
        'networking_effective' => false,
        'networking_checked_at' => '2026-07-26T12:00:00+00:00',
    ];
    $service->save();

    SSH::fake();

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson([
            'supported' => true,
            'pending' => false,
            'enabled' => true,
            'effective' => false,
            'checked_at' => '2026-07-26T12:00:00+00:00',
            'port' => 5432,
            'requires_remote_users' => false,
        ]);

    SSH::assertNotExecutedContains('SHOW listen_addresses');
});

test('resource supports networking', function (string $name, string $version) {
    $service = vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService($name, $version);

    $payload = (new ServiceResource($service))->toArray(request());

    expect($payload['supports_networking'])->toBeTrue();
    expect($payload['networking_enabled'])->toBeFalse();
})->with('databases');

function vitoPestFeatureServiceNetworkingDatabaseTestDatabaseService(string $name, string $version): Service
{
    test()->server->services()->where('type', 'database')->delete();

    return Service::factory()->create([
        'server_id' => test()->server->id,
        'type' => 'database',
        'name' => $name,
        'version' => $version,
        'status' => ServiceStatus::READY,
    ]);
}

/**
 * @return array<array<string>>
 */
dataset('databases', function () {
    return [
        ['mysql', '8.4'],
        ['mariadb', '11.4'],
        ['postgresql', '16'],
    ];
});
