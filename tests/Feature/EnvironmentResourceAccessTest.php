<?php

use App\Actions\Component\DeleteComponent;
use App\Actions\Component\SyncLoadBalancerBackends;
use App\Actions\Database\DeleteDatabase;
use App\Actions\Database\DeleteDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Actions\Database\UpdateDatabaseUser;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\EnvironmentAttachment\AttachDatabase;
use App\Actions\EnvironmentAttachment\AttachRedis;
use App\Actions\EnvironmentAttachment\DetachEnvironmentResource;
use App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess;
use App\Actions\EnvironmentAttachment\ValidateEnvironmentResource;
use App\Actions\Network\CheckEnvironmentResourceNetwork;
use App\Actions\Network\RemoveServerFromNetwork;
use App\Actions\Service\ManageNetworkingSecret;
use App\Actions\Service\ToggleNetworking;
use App\Actions\Service\UpdateConfigFile;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentAttachmentStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Helpers\AttachmentVariables;
use App\Jobs\EnvironmentAttachment\SyncEnvironmentResourceAccessJob;
use App\Jobs\Service\ToggleNetworkingJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Environment;
use App\Models\FirewallRule;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Support\Testing\SSHFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake('');
    Http::preventStrayRequests();
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom']);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $this->database = Database::factory()->for($this->server)->create(['name' => 'app_data']);
    $this->sync = app(SyncEnvironmentResourceAccess::class);
});

function resourcePrivateClient(Component $component, Server $resource): NetworkServer
{
    $client = Server::factory()->create(['project_id' => $resource->project_id]);
    $component->update(['server_id' => $client->id]);
    $network = Network::factory()->create(['project_id' => $resource->project_id, 'cidr' => '10.50.0.0/24']);
    NetworkServer::factory()->for($network)->for($resource)->create(['ip' => '10.50.0.1']);

    return NetworkServer::factory()->for($network)->for($client)->create(['ip' => '10.50.0.2']);
}

test('resource access uses component private addresses and enables networking behind managed firewall rules', function (string $engine, bool $remote) {
    if ($remote) {
        resourcePrivateClient($this->component, $this->server);
    }
    $memory = in_array($engine, ['redis', 'valkey'], true);
    $service = $memory ? $this->server->memoryDatabase() : $this->server->database();
    $service->name = $engine;
    $service->version = $engine === 'postgresql' ? '16' : $service->version;
    $service->save();
    $attachment = $memory
        ? app(AttachRedis::class)->attach($this->environment, ['service_id' => $service->id])
        : app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $ready = $this->sync->sync($attachment);
    if ($remote || $memory) {
        expect($ready)->toBeFalse()->and($service->fresh()->status)->toBe(ServiceStatus::RESTARTING);
        expect(app(ValidateEnvironmentResource::class)->ready($this->environment))->toBeFalse();
        Queue::assertPushed(ToggleNetworkingJob::class);
        SSH::fake("Active: active\n*\nbind 0.0.0.0 -::\nmysqlx_bind_address\t127.0.0.1");
        (new ToggleNetworkingJob($service->fresh(), true, ServiceStatus::READY))->handle();
        expect($service->fresh()->type_data['networking'])->toBeTrue();
        expect($this->sync->sync($attachment))->toBeTrue();
    } else {
        expect($ready)->toBeTrue();
        Queue::assertNotPushed(ToggleNetworkingJob::class);
    }
    $attachment->refresh();
    expect($attachment->status)->toBe(EnvironmentAttachmentStatus::READY)
        ->and($attachment->access[$this->component->id]['host'])->toBe($remote ? '10.50.0.2' : '127.0.0.1')
        ->and(app(ValidateEnvironmentResource::class)->ready($this->environment))->toBeTrue();
    $this->assertDatabaseHas('firewall_rules', [
        'environment_attachment_id' => $attachment->id, 'source' => $remote ? '10.50.0.2' : '127.0.0.1',
        'port' => (string) $service->handler()->networkingPort(), 'mask' => '32', 'type' => 'allow', 'component_managed' => true,
    ]);
    $variables = AttachmentVariables::for($attachment, $this->component->fresh());
    expect($variables[$memory ? 'REDIS_HOST' : 'DATABASE_HOST'])->toBe($remote ? '10.50.0.1' : '127.0.0.1');
    if ($memory) {
        expect($attachment->credentials['password'])->toBe($service->fresh()->secret)->not->toBeEmpty();
        expect($attachment->databaseUsers()->count())->toBe(0);
    } else {
        $user = $attachment->databaseUsers()->where('host', '!=', 'localhost')->sole();
        expect($user->host)->toBe($remote ? '10.50.0.2' : '127.0.0.1');
        expect($user->databases)->toBe([$this->database->name]);
        expect($variables['DATABASE_USER'])->toBe($user->username);
    }
    expect($this->sync->sync($attachment))->toBeTrue();
    expect($attachment->firewallRules()->count())->toBe($engine === 'mysql' ? 3 : 2);
    expect($attachment->databaseUsers()->count())->toBe($memory ? 0 : ($engine === 'postgresql' ? 2 : 1));
})->with(['mysql', 'mariadb', 'postgresql', 'redis', 'valkey'])->with([false, true]);

test('detach removes only managed access while preserving databases users ordinary rules and networking', function (string $engine) {
    $this->server->database()->update(['name' => $engine, 'type_data' => ['networking' => true]]);
    $port = $this->server->database()->handler()->networkingPort();
    $ordinary = FirewallRule::factory()->for($this->server)->create(['port' => (string) $port, 'source' => null, 'mask' => null, 'type' => 'allow']);
    $user = DatabaseUser::factory()->for($this->server)->create(['databases' => [$this->database->name]]);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    SSH::assertNotExecutedContains('sudo ufw allow from any to any proto tcp port '.$port);
    app(DetachEnvironmentResource::class)->detach($attachment);
    expect(app(ValidateEnvironmentResource::class)->ready($this->environment))->toBeFalse();
    $this->sync->sync($attachment);
    $this->assertDatabaseMissing('environment_attachments', ['id' => $attachment->id]);
    $this->assertDatabaseHas('databases', ['id' => $this->database->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('database_users', ['id' => $user->id]);
    $this->assertDatabaseHas('firewall_rules', ['id' => $ordinary->id]);
    SSH::assertExecutedContains('sudo ufw allow from any to any proto tcp port '.$port);
    expect($this->server->database()->type_data['networking'])->toBeTrue();
    expect(DatabaseUser::query()->count())->toBe(1);
    SSH::assertNotExecutedContains('DROP DATABASE');
    if ($engine === 'postgresql') {
        SSH::assertExecutedContains('REASSIGN OWNED BY');
        SSH::assertExecutedContains('DROP USER IF EXISTS');
    }
})->with(['mysql', 'postgresql']);

test('explicit allow rules for an attached port keep access above the resource guard', function () {
    $port = $this->server->database()->handler()->networkingPort();
    FirewallRule::factory()->for($this->server)->create(['port' => (string) $port, 'source' => '10.9.9.9', 'mask' => '32', 'type' => 'allow']);
    FirewallRule::factory()->for($this->server)->create(['port' => (string) $port, 'source' => null, 'mask' => null, 'type' => 'allow']);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);

    $script = collect(SSH::getExecutedCommands())->last(fn (string $command): bool => str_contains($command, 'ufw --force reset'));
    expect(strpos($script, 'sudo ufw allow from 10.9.9.9/32 to any proto tcp port '.$port))->toBeInt()
        ->toBeLessThan(strpos($script, 'sudo ufw deny from any to any proto tcp port '.$port))
        ->and($script)->toContain('-A vito-resource-guard -p tcp -s 10.9.9.9/32 --dport '.$port.' -j RETURN')
        ->not->toContain('sudo ufw allow from any to any proto tcp port '.$port);
});

test('resource firewall dependencies require a successful guard and are removed before stopping it', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);

    SSH::assertExecutedContains('Requires=vito-resource-guard.service');
    SSH::assertExecutedContains('After=vito-resource-guard.service');
    SSH::assertExecutedContains('postgresql@.service');
    SSH::assertExecutedContains('50-vito-resource-guard.conf');

    SSH::fake('');
    app(DetachEnvironmentResource::class)->detach($attachment);
    $this->sync->sync($attachment);

    $cleanup = collect(SSH::getExecutedCommands())->first(fn ($command) => str_contains($command, 'systemctl disable --now vito-resource-guard.service'));
    expect($cleanup)->not->toBeNull();
    expect(strpos($cleanup, 'rm -f "/etc/systemd/system/$unit.d/50-vito-resource-guard.conf"'))
        ->toBeLessThan(strpos($cleanup, 'systemctl daemon-reload'));
    expect(strpos($cleanup, 'systemctl daemon-reload'))
        ->toBeLessThan(strpos($cleanup, 'systemctl disable --now vito-resource-guard.service'));
});

test('failed provisioning retains ownership and can be retried or detached without orphaning users', function (bool $detach) {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $ssh = Mockery::mock(SSHFake::class, [''])->makePartial();
    $ssh->shouldReceive('exec')->withArgs(fn ($command, $log = '') => $log === 'create-user')->andThrow(new SSHCommandError('create interrupted'));
    SSH::swap($ssh);
    expect(fn () => $this->sync->sync($attachment))->toThrow(SSHCommandError::class);
    $user = $attachment->databaseUsers()->sole();
    $this->assertDatabaseHas('database_users', ['id' => $user->id, 'status' => 'creating', 'environment_attachment_id' => $attachment->id]);
    $this->sync->failed($attachment->id);
    SSH::fake('');
    if ($detach) {
        app(DetachEnvironmentResource::class)->detach($attachment);
    } else {
        $this->sync->retry($attachment);
    }
    $this->sync->sync($attachment);
    if ($detach) {
        $this->assertDatabaseMissing('database_users', ['id' => $user->id]);
    } else {
        $this->assertDatabaseHas('database_users', ['id' => $user->id, 'status' => 'ready']);
        expect($attachment->databaseUsers()->count())->toBe(1);
    }
})->with([false, true]);

test('failed detach keeps cleanup custody and supports a retry', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    app(DetachEnvironmentResource::class)->detach($attachment);
    SSH::fake()->execWillFail();
    expect(fn () => $this->sync->sync($attachment))->toThrow(SSHCommandError::class);
    $this->sync->failed($attachment->id);
    $this->assertDatabaseHas('environment_attachments', ['id' => $attachment->id, 'status' => 'deletion_failed']);
    expect($attachment->databaseUsers()->count())->toBe(1);
    SSH::fake('');
    $this->sync->retry($attachment);
    $this->sync->sync($attachment);
    $this->assertDatabaseMissing('environment_attachments', ['id' => $attachment->id]);
});

test('resource guards reject managed user changes database and service deletion and networking disable', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    $user = $attachment->databaseUsers()->sole();
    expect(fn () => app(DeleteDatabase::class)->delete($this->server, $this->database))->toThrow(ValidationException::class);
    expect(fn () => app(DeleteDatabaseUser::class)->delete($this->server, $user))->toThrow(ValidationException::class);
    expect(fn () => app(UpdateDatabaseUser::class)->update($user, ['password' => 'changed', 'permission' => 'read']))->toThrow(ValidationException::class);
    expect(fn () => app(LinkUser::class)->link($user, ['databases' => []]))->toThrow(ValidationException::class);
    expect(fn () => app(ToggleNetworking::class)->disable($this->server->database()))->toThrow(ValidationException::class);
    expect(Validator::make(['service' => $this->server->database()->id], $this->server->database()->handler()->deletionRules())->fails())->toBeTrue();
    expect(Validator::make(['service' => $this->server->firewall()->id], $this->server->firewall()->handler()->deletionRules())->fails())->toBeTrue();
    expect(fn () => $this->server->delete())->toThrow(ValidationException::class);
});

test('redis and valkey attachments protect their service and credential settings', function (string $engine) {
    $service = $this->server->memoryDatabase();
    $service->secret = 'retained-password';
    $service->update(['name' => $engine, 'type_data' => ['networking' => true]]);
    $attachment = app(AttachRedis::class)->attach($this->environment, ['service_id' => $service->id]);
    $this->sync->sync($attachment);
    expect(fn () => app(ManageNetworkingSecret::class)->regenerate($service))->toThrow(ValidationException::class);
    expect(fn () => app(ManageNetworkingSecret::class)->remove($service))->toThrow(ValidationException::class);
    expect(fn () => app(ToggleNetworking::class)->disable($service))->toThrow(ValidationException::class);
    expect(Validator::make(['service' => $service->id], $service->handler()->deletionRules())->fails())->toBeTrue();
    app(DetachEnvironmentResource::class)->detach($attachment);
    $this->sync->sync($attachment);
    expect($service->fresh()->secret)->toBe('retained-password')->and($service->fresh()->type_data['networking'])->toBeTrue();
})->with(['redis', 'valkey']);

test('resource access follows membership changes and withdraws access when a private network disappears', function () {
    $member = resourcePrivateClient($this->component, $this->server);
    $this->server->database()->jsonUpdate('type_data', 'networking', true);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    expect(fn () => app(RemoveServerFromNetwork::class)->remove($member))->toThrow(ValidationException::class);
    $oldUser = $attachment->databaseUsers()->sole()->id;
    $member->update(['ip' => '10.50.0.3']);
    Queue::assertPushed(SyncEnvironmentResourceAccessJob::class);
    $this->sync->sync($attachment);
    $this->assertDatabaseMissing('database_users', ['id' => $oldUser]);
    expect($attachment->databaseUsers()->sole()->host)->toBe('10.50.0.3');
    $member->update(['status' => NetworkServerStatus::LEAVING]);
    expect(fn () => $this->sync->sync($attachment))->toThrow(RuntimeException::class);
    expect($attachment->fresh()->access)->toBe([]);
    expect($attachment->databaseUsers()->count())->toBe(0);
    expect($attachment->firewallRules()->where('type', 'allow')->count())->toBe(0);
});

test('routine member resyncs do not mark the environment for redeploy', function () {
    $member = resourcePrivateClient($this->component, $this->server);
    $this->server->database()->jsonUpdate('type_data', 'networking', true);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    $version = $this->environment->fresh()->variables_version;
    Queue::fake();
    $member->update(['status' => NetworkServerStatus::UPDATING]);
    $member->update(['status' => NetworkServerStatus::ACTIVE]);
    expect($this->environment->fresh()->variables_version)->toBe($version);
    Queue::assertNotPushed(SyncEnvironmentResourceAccessJob::class);
    $member->update(['status' => NetworkServerStatus::FAILED]);
    expect($this->environment->fresh()->variables_version)->toBe($version + 1);
    Queue::assertPushed(SyncEnvironmentResourceAccessJob::class);
});

test('component removal and server purge dispatch cleanup on the resource server', function (bool $purge) {
    $member = resourcePrivateClient($this->component, $this->server);
    $this->server->database()->jsonUpdate('type_data', 'networking', true);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    Queue::fake();
    if ($purge) {
        $server = $member->server;
        $server->deleteFromProvider = false;
        $server->delete();
    } else {
        $this->component->status = ComponentStatus::DELETING;
        $this->component->save();
        app(DeleteComponent::class)->handle($this->component->id);
    }
    Queue::assertPushed(SyncEnvironmentResourceAccessJob::class, fn ($job) => $job->serverId === $this->server->id);
    $this->sync->sync($attachment);
    expect($attachment->fresh()->access)->toBe([])->and($attachment->databaseUsers()->count())->toBe(0);
})->with([false, true]);

test('environment teardown retains its resource until cleanup is complete even without components', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    $this->component->delete();
    Bus::fake();
    app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]);
    $this->assertDatabaseHas('environment_attachments', ['id' => $attachment->id, 'status' => 'deleting']);
    expect(fn () => app(DeleteEnvironment::class)->handle($this->environment->id))->toThrow(RuntimeException::class);
    $this->sync->sync($attachment);
    app(DeleteEnvironment::class)->handle($this->environment->id);
    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
    $this->assertDatabaseHas('databases', ['id' => $this->database->id, 'deleted_at' => null]);
});

test('load balancer synchronization does not delete or adopt resource firewall rules', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    $ids = $attachment->firewallRules()->pluck('id')->all();
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    expect($attachment->firewallRules()->pluck('id')->all())->toBe($ids);
});

test('cleanup keeps departed component network memberships protected without blocking unrelated members', function () {
    $member = resourcePrivateClient($this->component, $this->server);
    $this->server->database()->jsonUpdate('type_data', 'networking', true);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    $this->component->delete();
    $unrelated = NetworkServer::factory()->for($member->network)->create(['ip' => '10.50.0.9']);
    app(CheckEnvironmentResourceNetwork::class)->validate($unrelated);
    expect(fn () => app(RemoveServerFromNetwork::class)->remove($member))->toThrow(ValidationException::class);
    $this->sync->sync($attachment);
    app(CheckEnvironmentResourceNetwork::class)->validate($member);
    expect($attachment->fresh()->access)->toBe([]);
});

test('IPv6 resources upgrade existing IPv4 networking and use host-scoped firewall and bracketed URLs', function (string $engine) {
    $member = resourcePrivateClient($this->component, $this->server);
    $member->network->update(['cidr' => 'fd50::/64']);
    $member->network->servers()->where('server_id', $this->server->id)->update(['ip' => 'fd50::1']);
    $member->update(['ip' => 'fd50::2']);
    $memory = in_array($engine, ['redis', 'valkey'], true);
    $service = $memory ? $this->server->memoryDatabase() : $this->server->database();
    $service->secret = 'ipv6-password';
    $service->update(['name' => $engine, 'type_data' => ['networking' => true]]);
    $attachment = $memory
        ? app(AttachRedis::class)->attach($this->environment, ['service_id' => $service->id])
        : app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    expect($this->sync->sync($attachment))->toBeFalse();
    SSH::assertExecutedContains('Resource access requires IPv4 and IPv6 firewall protection.');
    SSH::fake("Active: active\n*\nbind 0.0.0.0 -::\nmysqlx_bind_address\t127.0.0.1\nVITO_IPV6_LISTENER");
    (new ToggleNetworkingJob($service->fresh(), true, ServiceStatus::READY))->handle();
    expect($service->fresh()->type_data['networking_dual_stack'])->toBeTrue();
    expect($this->sync->sync($attachment))->toBeTrue();
    $this->assertDatabaseHas('firewall_rules', ['environment_attachment_id' => $attachment->id, 'source' => 'fd50::2', 'mask' => '128', 'type' => 'allow']);
    $variables = AttachmentVariables::for($attachment->fresh(), $this->component->fresh());
    expect($variables[$memory ? 'REDIS_URL' : 'DATABASE_URL'])->toContain('@[fd50::1]:');
    if (! $memory) {
        expect($attachment->databaseUsers()->where('host', '!=', 'localhost')->sole()->host)->toBe('fd50::2');
    }
    if ($engine === 'postgresql') {
        SSH::assertExecutedContains('host all all ::/0 scram-sha-256');
    }
})->with(['mysql', 'mariadb', 'postgresql', 'redis', 'valkey']);

test('IPv6 Redis attachments cannot become ready after an IPv4-only networking verification', function () {
    $member = resourcePrivateClient($this->component, $this->server);
    $member->network->update(['cidr' => 'fd50::/64']);
    $member->network->servers()->where('server_id', $this->server->id)->update(['ip' => 'fd50::1']);
    $member->update(['ip' => 'fd50::2']);
    $service = $this->server->memoryDatabase();
    $service->update(['secret' => 'ipv6-password', 'type_data' => ['networking' => true]]);
    $attachment = app(AttachRedis::class)->attach($this->environment, ['service_id' => $service->id]);
    expect($this->sync->sync($attachment))->toBeFalse();
    SSH::fake("Active: active\nbind 0.0.0.0");

    (new ToggleNetworkingJob($service->fresh(), true, ServiceStatus::READY))->handle();

    expect($service->fresh()->type_data['networking_dual_stack'])->toBeFalse()
        ->and($this->sync->sync($attachment))->toBeFalse()
        ->and($attachment->fresh()->access[$this->component->id]['ready'])->toBeFalse();
    $this->assertDatabaseHas('environment_attachments', ['id' => $attachment->id, 'status' => EnvironmentAttachmentStatus::PENDING]);
});

test('a failure in the final firewall cleanup preserves the attachment and rule custody for retry', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    app(DetachEnvironmentResource::class)->detach($attachment);
    $ssh = Mockery::mock(SSHFake::class, [''])->makePartial();
    $attempt = 0;
    $ssh->shouldReceive('exec')->withArgs(fn ($command, $log = '') => $log === 'apply-rules')->andReturnUsing(function () use (&$attempt): string {
        if (++$attempt === 2) {
            throw new SSHCommandError('final firewall failed');
        }

        return '';
    });
    SSH::swap($ssh);
    expect(fn () => $this->sync->sync($attachment))->toThrow(SSHCommandError::class);
    expect($attachment->fresh())->not->toBeNull()->and($attachment->firewallRules()->count())->toBe(3);
    SSH::fake('');
    $this->sync->sync($attachment);
    $this->assertDatabaseMissing('environment_attachments', ['id' => $attachment->id]);
});

test('attached service configuration cannot bypass managed networking or password guards', function () {
    app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    expect(fn () => app(UpdateConfigFile::class)->update($this->server->database(), ['config_name' => 'mysql', 'content' => 'bind-address=0.0.0.0']))->toThrow(ValidationException::class);
    SSH::assertNotExecutedContains('systemctl restart');
});

test('resource jobs release on lock contention and while a service networking restart is pending', function () {
    $attachment = app(AttachRedis::class)->attach($this->environment, ['service_id' => $this->server->memoryDatabase()->id]);
    $lock = Cache::lock('unique-queue:server-'.$this->server->id, 600);
    $lock->get();
    $job = (new SyncEnvironmentResourceAccessJob($attachment->id, $this->server->id))->withFakeQueueInteractions();
    $job->handle();
    $job->assertReleased(30);
    $lock->release();
    $job = (new SyncEnvironmentResourceAccessJob($attachment->id, $this->server->id))->withFakeQueueInteractions();
    $job->handle();
    $job->assertReleased(10);
    expect($attachment->fresh()->status)->toBe(EnvironmentAttachmentStatus::PENDING);
});

test('PostgreSQL retains a nonlogin owner and preserves component roles through address changes', function () {
    $member = resourcePrivateClient($this->component, $this->server);
    $this->server->database()->update(['name' => 'postgresql', 'version' => '16', 'type_data' => ['networking' => true]]);
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    $owner = $attachment->databaseUsers()->where('host', 'localhost')->sole();
    $user = $attachment->databaseUsers()->where('host', $member->ip)->sole();
    SSH::assertExecutedContains('CREATE ROLE "'.$owner->username.'" WITH NOLOGIN');
    SSH::assertExecutedContains('GRANT "'.$owner->username.'" TO "'.$user->username.'"');
    SSH::assertExecutedContains('SET role TO \''.$owner->username.'\'');
    $member->update(['ip' => '10.50.0.3']);
    SSH::fake('');
    $this->sync->sync($attachment);
    $this->assertDatabaseHas('database_users', ['id' => $user->id, 'host' => '10.50.0.3']);
    SSH::assertNotExecutedContains('DROP USER');
    $this->component->delete();
    $this->sync->sync($attachment);
    $this->assertDatabaseMissing('database_users', ['id' => $user->id]);
    $this->assertDatabaseHas('database_users', ['id' => $owner->id]);
    SSH::assertExecutedContains('REASSIGN OWNED BY "'.$user->username.'" TO "'.$owner->username.'"');
    app(DetachEnvironmentResource::class)->detach($attachment);
    $this->sync->sync($attachment);
    SSH::assertExecutedContains('REASSIGN OWNED BY "'.$owner->username.'" TO "postgres"');
    $this->assertDatabaseCount('database_users', 0);
});

test('unconfirmed PostgreSQL roles do not poison another attachments cleanup or ordinary grants', function () {
    $this->server->database()->update(['name' => 'postgresql', 'version' => '16']);
    $first = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $environment = Environment::factory()->for($this->application)->create();
    Component::factory()->for($environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    $second = app(AttachDatabase::class)->attach($environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $ssh = Mockery::mock(SSHFake::class, [''])->makePartial();
    $ssh->shouldReceive('exec')->withArgs(fn ($command, $log = '') => $log === 'create-user')->andThrow(new SSHCommandError('create interrupted'));
    SSH::swap($ssh);
    foreach ([$first, $second] as $attachment) {
        expect(fn () => $this->sync->sync($attachment))->toThrow(SSHCommandError::class);
    }
    SSH::fake('');
    $user = DatabaseUser::factory()->for($this->server)->create();
    app(LinkUser::class)->link($user, ['databases' => [$this->database->name]]);
    app(DetachEnvironmentResource::class)->detach($first);
    $this->sync->sync($first);
    SSH::assertNotExecutedContains('TO "'.$second->credentials['username'].'"');
    SSH::assertNotExecutedContains('FOR ROLE "'.$second->credentials['username'].'"');
    $this->assertDatabaseHas('database_users', ['environment_attachment_id' => $second->id, 'status' => 'creating']);
});

test('networking enable cannot bypass resource firewall failure and guards custom MySQL X ports', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $ssh = Mockery::mock(SSHFake::class, ["mysqlx_port\t33070"])->makePartial();
    $ssh->shouldReceive('exec')->withArgs(fn ($command, $log = '') => $log === 'apply-rules')->andThrow(new SSHCommandError('firewall unavailable'));
    SSH::swap($ssh);
    $job = (new ToggleNetworkingJob($this->server->database(), true, ServiceStatus::READY))->withFakeQueueInteractions();
    $job->handle();
    $job->assertFailedWith(SSHCommandError::class);
    $this->assertDatabaseHas('firewall_rules', ['environment_attachment_id' => $attachment->id, 'port' => '33070', 'type' => 'deny', 'component_managed' => true]);
    SSH::assertNotExecutedContains('bind-address = *');
    SSH::assertNotExecutedContains('systemctl restart');
});

test('resource firewall updates and removals broadcast the established table events', function () {
    $attachment = app(AttachDatabase::class)->attach($this->environment, ['server_id' => $this->server->id, 'database_id' => $this->database->id]);
    $this->sync->sync($attachment);
    $ids = $attachment->firewallRules()->pluck('id')->all();
    foreach ($ids as $id) {
        Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'firewall-rule.updated' && $event->data->data['id'] === $id);
    }
    app(DetachEnvironmentResource::class)->detach($attachment);
    $this->sync->sync($attachment);
    foreach ($ids as $id) {
        Event::assertDispatched(SocketEvent::class, fn ($event) => $event->data->type === 'firewall-rule.deleted' && $event->data->data['id'] === $id);
    }
});

test('database deletion and configuration uploads hold the attachment admission mutex', function () {
    $ssh = Mockery::mock(SSHFake::class, [''])->makePartial();
    $key = 'unique-queue:server-'.$this->server->id;
    $assertLocked = function () use ($key): void {
        expect(Cache::lock($key, 600)->get())->toBeFalse();
    };
    $ssh->shouldReceive('exec')->withArgs(fn ($command, $log = '') => $log === 'delete-database')->once()->andReturnUsing(function () use ($assertLocked): string {
        $assertLocked();

        return '';
    });
    $ssh->shouldReceive('upload')->once()->andReturnUsing($assertLocked);
    SSH::swap($ssh);
    app(DeleteDatabase::class)->delete($this->server, $this->database);
    $service = $this->server->database();
    $configName = config('service.services.mysql.config_paths.0.name');
    app(UpdateConfigFile::class)->update($service, ['config_name' => $configName, 'content' => 'bind-address=127.0.0.1']);
    $lock = Cache::lock($key, 600);
    expect($lock->get())->toBeTrue();
    $lock->release();
});
