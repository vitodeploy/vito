<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Testing\SSHFake;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceNetworkingDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_mysql_networking(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');

        SSH::fake("Active: active\n0.0.0.0\nmysqlx_bind_address\t0.0.0.0");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::READY, $service->status);
        $this->assertNull($service->secret);

        SSH::assertExecutedContains('sudo mkdir -p /etc/mysql/mysql.conf.d');
        SSH::assertExecutedContains('sudo cp /etc/mysql/mysql.conf.d/zz-vito-networking.cnf /etc/mysql/mysql.conf.d/zz-vito-networking.cnf.vito.bak');
        SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'0.0.0.0\' | sudo tee /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
        SSH::assertExecutedContains('printf \'loose-mysqlx-bind-address = %s\n\' \'0.0.0.0\' | sudo tee -a /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
        SSH::assertExecutedContains('sudo systemctl restart mysql');
        SSH::assertExecutedContains('sudo mysql -N -e "SELECT @@bind_address"');
        SSH::assertExecutedContains('sudo mysql -N -e "SHOW VARIABLES LIKE \'mysqlx_bind_address\'"');
        SSH::assertNotExecutedContains('.vito.bak /etc/mysql/mysql.conf.d/zz-vito-networking.cnf', 'The drop-in must not be rolled back.');
    }

    public function test_disable_mysql_networking(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');
        $service->type_data = ['networking' => true];
        $service->save();

        SSH::fake("Active: active\n127.0.0.1\nmysqlx_bind_address\t127.0.0.1");

        $this->postJson(route('services.networking.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertFalse($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::READY, $service->status);

        SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'127.0.0.1\' | sudo tee /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
        SSH::assertExecutedContains('printf \'loose-mysqlx-bind-address = %s\n\' \'127.0.0.1\' | sudo tee -a /etc/mysql/mysql.conf.d/zz-vito-networking.cnf > /dev/null');
        SSH::assertExecutedContains('sudo systemctl restart mysql');
        SSH::assertNotExecutedContains('0.0.0.0');
    }

    public function test_enable_mysql_networking_tolerates_a_disabled_x_plugin(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');

        SSH::fake("Active: active\n0.0.0.0");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::READY, $service->status);

        SSH::assertExecutedContains('sudo mysql -N -e "SHOW VARIABLES LIKE \'mysqlx_bind_address\'"');
        SSH::assertNotExecutedContains('SELECT @@mysqlx_bind_address');
    }

    public function test_enable_mysql_networking_fails_when_the_x_plugin_stays_local(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');

        SSH::fake("Active: active\n0.0.0.0\nmysqlx_bind_address\t127.0.0.1");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertFalse($service->type_data['networking']);
        $this->assertTrue($service->type_data['networking_failed']);

        SSH::assertExecutedContains('sudo rm -f /etc/mysql/mysql.conf.d/zz-vito-networking.cnf');
    }

    public function test_enable_mariadb_networking_does_not_manage_the_x_plugin(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mariadb', '11.4');

        SSH::fake("Active: active\n0.0.0.0");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::READY, $service->status);

        SSH::assertExecutedContains('sudo mkdir -p /etc/mysql/mariadb.conf.d');
        SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'0.0.0.0\' | sudo tee /etc/mysql/mariadb.conf.d/zz-vito-networking.cnf > /dev/null');
        SSH::assertExecutedContains('sudo systemctl restart mariadb');
        SSH::assertExecutedContains('sudo mariadb -N -e "SELECT @@bind_address"');
        SSH::assertNotExecutedContains('loose-mysqlx-bind-address', 'MariaDB has no X plugin.');
        SSH::assertNotExecutedContains('mysqlx_bind_address', 'MariaDB has no X plugin.');
        SSH::assertNotExecutedContains('sudo mysql -N', 'MariaDB must use the mariadb client.');
    }

    public function test_disable_mariadb_networking(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mariadb', '11.4');
        $service->type_data = ['networking' => true];
        $service->save();

        SSH::fake("Active: active\n127.0.0.1");

        $this->postJson(route('services.networking.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $this->assertFalse($service->refresh()->type_data['networking']);

        SSH::assertExecutedContains('printf \'[mysqld]\nbind-address = %s\n\' \'127.0.0.1\' | sudo tee /etc/mysql/mariadb.conf.d/zz-vito-networking.cnf > /dev/null');
        SSH::assertNotExecutedContains('loose-mysqlx-bind-address');
    }

    public function test_enable_postgresql_networking(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('postgresql', '16');

        SSH::fake("Active: active\n0.0.0.0");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::READY, $service->status);

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
    }

    public function test_disable_postgresql_networking(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('postgresql', '16');
        $service->type_data = ['networking' => true];
        $service->save();

        SSH::fake("Active: active\nlocalhost");

        $this->postJson(route('services.networking.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertFalse($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::READY, $service->status);

        SSH::assertExecutedContains('sudo grep -Eq \'^[[:space:]]*include_dir[[:space:]]*=\' /etc/postgresql/16/main/postgresql.conf || printf "\ninclude_dir = \'conf.d\'\n" | sudo tee -a /etc/postgresql/16/main/postgresql.conf > /dev/null');
        SSH::assertExecutedContains('printf "listen_addresses = \'%s\'\n" \'localhost\' | sudo tee /etc/postgresql/16/main/conf.d/zz-vito-networking.conf > /dev/null');
        SSH::assertExecutedContains('sudo sed -i \'/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d\' /etc/postgresql/16/main/pg_hba.conf');
        SSH::assertExecutedContains('sudo systemctl restart postgresql');
        SSH::assertNotExecutedContains('scram-sha-256', 'The managed pg_hba block must not be written back on disable.');
        SSH::assertNotExecutedContains('sudo rm -f /etc/postgresql/16/main/conf.d/zz-vito-networking.conf', 'Disable rewrites the drop-in instead of deleting it.');
    }

    #[DataProvider('databases')]
    public function test_enable_networking_skips_the_restart_when_the_service_is_not_running(string $name, string $version): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService($name, $version);
        $service->status = ServiceStatus::STOPPED;
        $service->save();

        SSH::fake();

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertTrue($service->type_data['networking_effective']);
        $this->assertNull(
            $service->type_data['networking_checked_at'] ?? null,
            'A toggle that never restarted or verified must not stamp an observation timestamp.'
        );
        $this->assertEquals(ServiceStatus::STOPPED, $service->status);

        SSH::assertNotExecutedContains('systemctl restart');
        SSH::assertNotExecutedContains('SELECT @@bind_address');
        SSH::assertNotExecutedContains('SHOW listen_addresses');
    }

    public function test_failed_mysql_enable_rolls_back_the_drop_in_and_marks_the_service_as_failed(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');

        SSH::fake("Active: active\n127.0.0.1");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertFalse($service->type_data['networking']);
        $this->assertTrue($service->type_data['networking_failed']);

        SSH::assertExecutedContains('sudo rm -f /etc/mysql/mysql.conf.d/zz-vito-networking.cnf');
        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'enable-networking-failed',
        ]);
    }

    public function test_failed_mysql_enable_restores_a_previous_drop_in_instead_of_deleting_it(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');

        SSH::fake("Active: active\n127.0.0.1\nmysqlx_bind_address\t127.0.0.1");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);

        SSH::assertExecutedContains('sudo cp /etc/mysql/mysql.conf.d/zz-vito-networking.cnf.vito.bak /etc/mysql/mysql.conf.d/zz-vito-networking.cnf');
    }

    public function test_failed_mysql_disable_does_not_roll_back(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');
        $service->type_data = ['networking' => true];
        $service->save();

        SSH::fake("Active: active\n0.0.0.0");

        $this->postJson(route('services.networking.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertTrue($service->type_data['networking']);

        SSH::assertNotExecutedContains('.vito.bak /etc/mysql/mysql.conf.d/zz-vito-networking.cnf', 'The drop-in must not be rolled back.');
    }

    public function test_failed_postgresql_enable_restores_both_backups(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('postgresql', '16');

        SSH::fake("Active: active\nlocalhost");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertFalse($service->type_data['networking']);
        $this->assertTrue($service->type_data['networking_failed']);

        SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/postgresql.conf.vito.bak /etc/postgresql/16/main/postgresql.conf');
        SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/pg_hba.conf.vito.bak /etc/postgresql/16/main/pg_hba.conf');
        SSH::assertExecutedContains('sudo rm -f /etc/postgresql/16/main/conf.d/zz-vito-networking.conf');
    }

    public function test_failed_postgresql_config_write_rolls_back_the_drop_in(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('postgresql', '16');

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

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertFalse($service->type_data['networking']);
        $this->assertTrue($service->type_data['networking_failed']);

        SSH::assertExecutedContains('sudo rm -f /etc/postgresql/16/main/conf.d/zz-vito-networking.conf');
        SSH::assertExecutedContains('sudo cp /etc/postgresql/16/main/postgresql.conf.vito.bak /etc/postgresql/16/main/postgresql.conf');
    }

    public function test_failed_postgresql_disable_never_restores_the_open_state(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('postgresql', '16');
        $service->type_data = ['networking' => true];
        $service->save();

        SSH::fake("Active: active\n0.0.0.0");

        $this->postJson(route('services.networking.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertTrue($service->type_data['networking']);

        SSH::assertNotExecutedContains('.vito.bak /etc/postgresql/16/main/', 'A failed disable must never restore the open state.');
    }

    public function test_mysql_networking_details(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('mysql', '8.4');
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
    }

    public function test_postgresql_networking_details(): void
    {
        $this->actingAs($this->user);

        $service = $this->databaseService('postgresql', '16');
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
    }

    #[DataProvider('databases')]
    public function test_resource_supports_networking(string $name, string $version): void
    {
        $service = $this->databaseService($name, $version);

        $payload = (new ServiceResource($service))->toArray(request());

        $this->assertTrue($payload['supports_networking']);
        $this->assertFalse($payload['networking_enabled']);
    }

    private function databaseService(string $name, string $version): Service
    {
        $this->server->services()->where('type', 'database')->delete();

        return Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'database',
            'name' => $name,
            'version' => $version,
            'status' => ServiceStatus::READY,
        ]);
    }

    /**
     * @return array<array<string>>
     */
    public static function databases(): array
    {
        return [
            ['mysql', '8.4'],
            ['mariadb', '11.4'],
            ['postgresql', '16'],
        ];
    }
}
