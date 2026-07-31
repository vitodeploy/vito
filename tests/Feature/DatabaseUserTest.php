<?php

use App\Enums\DatabaseUserPermission;
use App\Enums\DatabaseUserStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Services\Database\Mysql;
use App\Services\Database\Postgresql;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('create database user', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->post(route('database-users.store', [
        'server' => $this->server,
    ]), [
        'username' => 'user',
        'password' => 'password',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'username' => 'user',
        'status' => DatabaseUserStatus::READY,
    ]);
});

test('create database user with remote', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->post(route('database-users.store', [
        'server' => $this->server,
    ]), [
        'username' => 'user',
        'password' => 'password',
        'remote' => true,
        'host' => '%',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'username' => 'user',
        'host' => '%',
        'status' => DatabaseUserStatus::READY,
    ]);
});

test('see database users list', function () {
    $this->actingAs($this->user);

    DatabaseUser::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->get(route('database-users', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('database-users/index'));
});

test('delete database user', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->delete(route('database-users.destroy', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]))->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('database_users', [
        'id' => $databaseUser->id,
    ]);
});

test('unlink database', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'databases' => [],
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'username' => $databaseUser->username,
        'databases' => $this->castAsJson([]),
    ]);
});

test('update database user password', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'password' => 'old_password',
    ]);

    $this->put(route('database-users.update', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'password' => 'new_password',
        'permission' => $databaseUser->permission->value,
    ])->assertSessionDoesntHaveErrors();

    $databaseUser->refresh();

    expect($databaseUser->password)->toEqual('new_password');
});

test('update database user host', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'host' => 'localhost',
    ]);

    $this->put(route('database-users.update', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'remote' => true,
        'host' => '%',
        'permission' => $databaseUser->permission->value,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'id' => $databaseUser->id,
        'host' => '%',
    ]);
});

test('update database user password and host', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'password' => 'old_password',
        'host' => 'localhost',
    ]);

    $this->put(route('database-users.update', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'password' => 'new_password',
        'remote' => true,
        'host' => '192.168.1.1',
        'permission' => $databaseUser->permission->value,
    ])->assertSessionDoesntHaveErrors();

    $databaseUser->refresh();

    expect($databaseUser->password)->toEqual('new_password');
    expect($databaseUser->host)->toEqual('192.168.1.1');
});

test('create database user with admin permission', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->post(route('database-users.store', [
        'server' => $this->server,
    ]), [
        'username' => 'user',
        'password' => 'password',
        'permission' => 'admin',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'username' => 'user',
        'permission' => 'admin',
        'status' => DatabaseUserStatus::READY,
    ]);
});

test('create database user with write permission', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->post(route('database-users.store', [
        'server' => $this->server,
    ]), [
        'username' => 'user',
        'password' => 'password',
        'permission' => 'write',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'username' => 'user',
        'permission' => 'write',
        'status' => DatabaseUserStatus::READY,
    ]);
});

test('create database user with read permission', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->post(route('database-users.store', [
        'server' => $this->server,
    ]), [
        'username' => 'user',
        'password' => 'password',
        'permission' => 'read',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'username' => 'user',
        'permission' => 'read',
        'status' => DatabaseUserStatus::READY,
    ]);
});

test('update database user permission', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $database = Database::factory()->create([
        'server_id' => $this->server,
        'name' => 'test_db',
    ]);

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'permission' => 'admin',
        'databases' => ['test_db'],
    ]);

    $this->put(route('database-users.update', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'permission' => 'read',
    ])->assertSessionDoesntHaveErrors();

    $databaseUser->refresh();

    expect($databaseUser->permission)->toEqual(DatabaseUserPermission::READ);
});

test('sync database users creates rows per host for mysql', function () {
    $this->actingAs($this->user);

    $mysqlFakeOutput = implode("\n", [
        "User\tHost\tPrivileges",
        "app\tlocalhost\tappdb",
        "app\t127.0.0.1\tappdb,devdb",
    ]);

    SSH::fake($mysqlFakeOutput);

    $this->patch(route('database-users.sync', ['server' => $this->server]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'server_id' => $this->server->id,
        'username' => 'app',
        'host' => 'localhost',
        'databases' => $this->castAsJson(['appdb']),
    ]);

    $this->assertDatabaseHas('database_users', [
        'server_id' => $this->server->id,
        'username' => 'app',
        'host' => '127.0.0.1',
        'databases' => $this->castAsJson(['appdb', 'devdb']),
    ]);

    expect(DatabaseUser::where('server_id', $this->server->id)->where('username', 'app')->count())->toBe(2);
});

test('sync database users is idempotent for mysql multi host', function () {
    $this->actingAs($this->user);

    $mysqlFakeOutput = implode("\n", [
        "User\tHost\tPrivileges",
        "app\tlocalhost\tappdb",
        "app\t127.0.0.1\tappdb,devdb",
    ]);

    SSH::fake($mysqlFakeOutput);

    $this->patch(route('database-users.sync', ['server' => $this->server]));
    $this->patch(route('database-users.sync', ['server' => $this->server]));

    expect(DatabaseUser::where('server_id', $this->server->id)->where('username', 'app')->count())->toBe(2);
});

test('sync database users does not duplicate postgresql rows', function () {
    $this->actingAs($this->user);

    $this->server->services()->where('type', Mysql::type())->delete();
    $this->server->services()->create([
        'type' => Postgresql::type(),
        'name' => Postgresql::id(),
        'version' => '15',
        'status' => ServiceStatus::READY,
    ]);
    $this->server->refresh();

    DatabaseUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'appuser',
        'host' => 'localhost',
        'databases' => [],
    ]);

    $pgFakeOutput = implode("\n", [
        ' username | host | databases',
        '----------+------+-----------',
        ' appuser  |      | appdb',
        '(1 row)',
    ]);

    SSH::fake($pgFakeOutput);

    $this->patch(route('database-users.sync', ['server' => $this->server]));
    $this->patch(route('database-users.sync', ['server' => $this->server]));

    expect(DatabaseUser::where('server_id', $this->server->id)->where('username', 'appuser')->count())->toBe(1);

    $this->assertDatabaseHas('database_users', [
        'server_id' => $this->server->id,
        'username' => 'appuser',
        'host' => 'localhost',
        'databases' => $this->castAsJson(['appdb']),
    ]);
});

test('create database user same username different host succeeds', function () {
    $this->actingAs($this->user);

    SSH::fake();

    DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'app',
        'host' => 'localhost',
    ]);

    $this->post(route('database-users.store', ['server' => $this->server]), [
        'username' => 'app',
        'password' => 'password',
        'remote' => true,
        'host' => '10.0.0.1',
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'server_id' => $this->server->id,
        'username' => 'app',
        'host' => '10.0.0.1',
    ]);

    expect(DatabaseUser::where('server_id', $this->server->id)->where('username', 'app')->count())->toBe(2);
});

test('create database user duplicate username and host fails', function () {
    $this->actingAs($this->user);

    SSH::fake();

    DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'app',
        'host' => '%',
    ]);

    $this->post(route('database-users.store', ['server' => $this->server]), [
        'username' => 'app',
        'password' => 'password',
        'remote' => true,
        'host' => '%',
    ])->assertSessionHasErrors('username');

    expect(DatabaseUser::where('server_id', $this->server->id)->where('username', 'app')->count())->toBe(1);
});

test('create database user duplicate username fails for postgresql', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    SSH::fake();

    DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'appuser',
        'host' => 'localhost',
    ]);

    $this->post(route('database-users.store', ['server' => $this->server]), [
        'username' => 'appuser',
        'password' => 'password',
    ])->assertSessionHasErrors('username');

    expect(DatabaseUser::where('server_id', $this->server->id)->where('username', 'appuser')->count())->toBe(1);
});

test('update database user host collision fails', function () {
    $this->actingAs($this->user);

    SSH::fake();

    DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'app',
        'host' => '10.0.0.1',
    ]);

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'app',
        'host' => 'localhost',
    ]);

    $this->put(route('database-users.update', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'remote' => true,
        'host' => '10.0.0.1',
        'permission' => $databaseUser->permission->value,
    ])->assertSessionHasErrors('host');

    $this->assertDatabaseHas('database_users', [
        'id' => $databaseUser->id,
        'host' => 'localhost',
    ]);
});

test('create database user rejects malicious host', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->post(route('database-users.store', ['server' => $this->server]), [
        'username' => 'user',
        'password' => 'password',
        'remote' => true,
        'host' => '$(touch /tmp/pwn)',
    ])->assertSessionHasErrors('host');

    $this->assertDatabaseMissing('database_users', [
        'username' => 'user',
    ]);
});

test('create database user with empty host defaults to localhost', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->post(route('database-users.store', ['server' => $this->server]), [
        'username' => 'user',
        'password' => 'password',
        'remote' => false,
        'host' => '',
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('database_users', [
        'username' => 'user',
        'host' => 'localhost',
    ]);
});

test('update synced postgresql user with empty host succeeds', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    SSH::fake();

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'appuser',
        'host' => '',
        'permission' => 'admin',
    ]);

    $this->put(route('database-users.update', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'remote' => true,
        'host' => '',
        'permission' => 'read',
    ])->assertSessionDoesntHaveErrors();

    $databaseUser->refresh();

    expect($databaseUser->permission)->toEqual(DatabaseUserPermission::READ);
    expect($databaseUser->host)->toBe('');
});

test('database users index shows host column for mysql', function () {
    $this->actingAs($this->user);

    $this->get(route('database-users', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('database-users/index')
            ->where('databaseUsers.columns', fn ($columns) => collect($columns)->contains(
                fn ($column) => ($column['name'] ?? null) === 'host' && empty($column['hidden'])
            ))
        );
});

test('database users index hides host column for postgresql', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    $this->get(route('database-users', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('database-users/index')
            ->where('databaseUsers.columns', fn ($columns) => collect($columns)->doesntContain(
                fn ($column) => ($column['name'] ?? null) === 'host' && empty($column['hidden'])
            ))
        );
});

test('postgresql multiple admins get cross default privileges', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    SSH::fake();

    Database::factory()->create(['server_id' => $this->server, 'name' => 'app']);

    $adminA = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'admin_a',
        'permission' => 'admin',
        'host' => '',
    ]);
    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $adminA,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    $adminB = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'admin_b',
        'permission' => 'admin',
        'host' => '',
    ]);
    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $adminB,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('ALTER DEFAULT PRIVILEGES FOR ROLE \"admin_a\" IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO \"admin_b\"');
    SSH::assertExecutedContains('ALTER DEFAULT PRIVILEGES FOR ROLE \"admin_b\" IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO \"admin_a\"');
});

test('postgresql write user is a creator with write default privileges', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    SSH::fake();

    Database::factory()->create(['server_id' => $this->server, 'name' => 'app']);

    $admin = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'admin_a',
        'permission' => 'admin',
        'host' => '',
    ]);
    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $admin,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    $writer = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'writer',
        'permission' => 'write',
        'host' => '',
    ]);
    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $writer,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('ALTER DEFAULT PRIVILEGES FOR ROLE \"admin_a\" IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES, TRIGGER ON TABLES TO \"writer\"');
    SSH::assertExecutedContains('ALTER DEFAULT PRIVILEGES FOR ROLE \"writer\" IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO \"admin_a\"');
});

test('postgresql read user is not a creator but is granted', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    SSH::fake();

    Database::factory()->create(['server_id' => $this->server, 'name' => 'app']);

    $admin = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'admin_a',
        'permission' => 'admin',
        'host' => '',
    ]);
    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $admin,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    $reader = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'reader',
        'permission' => 'read',
        'host' => '',
    ]);
    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $reader,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('ALTER DEFAULT PRIVILEGES FOR ROLE \"admin_a\" IN SCHEMA public GRANT SELECT ON TABLES TO \"reader\"');
    SSH::assertNotExecutedContains('FOR ROLE \"reader\" IN SCHEMA public GRANT');
});

test('postgresql v15 user gets schema create', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    SSH::fake();

    Database::factory()->create(['server_id' => $this->server, 'name' => 'app']);

    $admin = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'admin_a',
        'permission' => 'admin',
        'host' => '',
    ]);
    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $admin,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('GRANT USAGE, CREATE ON SCHEMA public TO \"admin_a\"');
});

test('postgresql deleting user revokes its default privileges before drop', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseUserTestUsePostgresql();

    SSH::fake();

    Database::factory()->create(['server_id' => $this->server, 'name' => 'app']);

    $adminA = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'admin_a',
        'permission' => 'admin',
        'host' => '',
        'databases' => ['app'],
    ]);
    $adminB = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'admin_b',
        'permission' => 'admin',
        'host' => '',
        'databases' => ['app'],
    ]);

    $this->delete(route('database-users.destroy', [
        'server' => $this->server,
        'databaseUser' => $adminB,
    ]))->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('ALTER DEFAULT PRIVILEGES FOR ROLE \"admin_b\" IN SCHEMA public REVOKE ALL ON TABLES FROM \"admin_b\"');
    SSH::assertExecutedContains('ALTER DEFAULT PRIVILEGES FOR ROLE \"admin_a\" IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO \"admin_a\"');
    $this->assertDatabaseMissing('database_users', ['id' => $adminB->id]);
});

test('mysql link does not run privilege reconcile', function () {
    $this->actingAs($this->user);

    SSH::fake();

    Database::factory()->create(['server_id' => $this->server, 'name' => 'app']);

    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
        'username' => 'app',
        'host' => 'localhost',
    ]);

    $this->put(route('database-users.link', [
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), ['databases' => ['app']])->assertSessionDoesntHaveErrors();

    SSH::assertNotExecutedContains('ALTER DEFAULT PRIVILEGES');
});

function vitoPestFeatureDatabaseUserTestUsePostgresql(): void
{
    test()->server->services()->where('type', Mysql::type())->delete();
    test()->server->services()->create([
        'type' => Postgresql::type(),
        'name' => Postgresql::id(),
        'version' => '15',
        'status' => ServiceStatus::READY,
    ]);
    test()->server->refresh();
}
