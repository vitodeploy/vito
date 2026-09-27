<?php

use App\Enums\OperatingSystem;
use App\Exceptions\ServerProviderError;
use App\Jobs\Server\InstallJob;
use App\Models\Server;
use App\Models\ServerProvider;
use App\ServerProviders\Proxmox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proxmox = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'provider' => Proxmox::id(),
        'credentials' => [
            'api_url' => 'https://pve.test:8006',
            'token_id' => 'vito@pve!vito',
            'token_secret' => 'secret',
            'verify_ssl' => true,
            'storage' => null,
            'template_ubuntu_24' => 9000,
        ],
    ]);

    $this->server->update([
        'provider' => Proxmox::id(),
        'provider_id' => $this->proxmox->id,
        'provider_data' => ['region' => 'pve', 'plan' => 'medium', 'vmid' => 101],
    ]);
    $this->server->refresh();
});

test('connect proxmox', function (string $apiUrl) {
    $this->actingAs($this->user);

    Http::fake([
        '*/api2/json/cluster/resources*' => Http::response(['data' => [
            ['vmid' => 9000, 'node' => 'pve', 'template' => 1, 'type' => 'qemu'],
        ]]),
        '*/api2/json/nodes/pve/qemu/9000/config' => Http::response(['data' => [
            'scsi0' => 'local-lvm:base-9000-disk-0,size=3584M',
            'ide2' => 'local-lvm:vm-9000-cloudinit,media=cdrom',
        ]]),
    ]);

    $this->post(route('server-providers.store'), [
        'provider' => Proxmox::id(),
        'name' => 'homelab',
        'api_url' => $apiUrl,
        'token_id' => 'vito@pve!vito',
        'token_secret' => 'secret',
        'verify_ssl' => false,
        'template_ubuntu_24' => '9000',
    ])->assertSessionDoesntHaveErrors();

    $provider = ServerProvider::query()->where('profile', 'homelab')->firstOrFail();

    expect($provider->credentials)->toMatchArray([
        'api_url' => 'https://pve.test:8006',
        'verify_ssl' => false,
        'template_ubuntu_22' => null,
        'template_ubuntu_24' => 9000,
    ]);

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'PVEAPIToken=vito@pve!vito=secret')
        && str_starts_with($request->url(), 'https://pve.test:8006/api2/json/cluster/resources'));
})->with([
    'host' => 'https://pve.test:8006/',
    'api path' => 'https://pve.test:8006/api2/json',
    'web ui url' => 'https://pve.test:8006/#v1:0:=qemu%2F100:4:::::::',
]);

test('cannot connect proxmox when the token sees no vms', function () {
    $this->actingAs($this->user);

    Http::fake(['*/api2/json/cluster/resources*' => Http::response(['data' => []])]);

    $this->post(route('server-providers.store'), [
        'provider' => Proxmox::id(),
        'name' => 'homelab',
        'api_url' => 'https://pve.test:8006',
        'token_id' => 'root@pam!vito',
        'token_secret' => 'secret',
        'template_ubuntu_24' => '100',
    ])->assertSessionHasErrors('token_id');
});

test('cannot connect proxmox when the url is not a proxmox api', function () {
    $this->actingAs($this->user);

    Http::fake(['*' => Http::response('<html>Proxmox Virtual Environment</html>')]);

    $this->post(route('server-providers.store'), [
        'provider' => Proxmox::id(),
        'name' => 'homelab',
        'api_url' => 'https://pve.test:8006',
        'token_id' => 'root@pam!vito',
        'token_secret' => 'secret',
        'template_ubuntu_24' => '100',
    ])->assertSessionHasErrors('provider');
});

test('cannot connect proxmox with an invalid token', function () {
    $this->actingAs($this->user);

    Http::fake(['*' => Http::response(['data' => null], 401)]);

    $this->post(route('server-providers.store'), [
        'provider' => Proxmox::id(),
        'name' => 'homelab',
        'api_url' => 'https://pve.test:8006',
        'token_id' => 'vito@pve!vito',
        'token_secret' => 'wrong',
        'template_ubuntu_24' => '9000',
    ])->assertSessionHasErrors('provider');

    $this->assertDatabaseMissing('server_providers', ['profile' => 'homelab']);
});

test('cannot connect proxmox with an invalid template mapping', function (array $input, array $vm, array $config, string $error) {
    $this->actingAs($this->user);

    Http::fake([
        '*/api2/json/cluster/resources*' => Http::response(['data' => [$vm]]),
        '*/api2/json/nodes/pve/qemu/9000/config' => Http::response(['data' => $config]),
    ]);

    $this->post(route('server-providers.store'), array_merge([
        'provider' => Proxmox::id(),
        'name' => 'homelab',
        'api_url' => 'https://pve.test:8006',
        'token_id' => 'vito@pve!vito',
        'token_secret' => 'secret',
    ], $input))->assertSessionHasErrors(['template_ubuntu_24' => $error]);

    $this->assertDatabaseMissing('server_providers', ['profile' => 'homelab']);
})->with([
    'no mapping' => [[], [], [], 'Map at least one Ubuntu version to a template.'],
    'missing vm' => [['template_ubuntu_24' => '9000'], ['vmid' => 100, 'node' => 'pve', 'template' => 1, 'type' => 'qemu'], [], 'VM 9000 was not found or the API token cannot access it.'],
    'not a template' => [['template_ubuntu_24' => '9000'], ['vmid' => 9000, 'node' => 'pve', 'template' => 0, 'type' => 'qemu'], [], 'VM 9000 is not a QEMU template.'],
    'container template' => [['template_ubuntu_24' => '9000'], ['vmid' => 9000, 'node' => 'pve', 'template' => 1, 'type' => 'lxc'], [], 'VM 9000 is not a QEMU template.'],
    'no cloud-init drive' => [['template_ubuntu_24' => '9000'], ['vmid' => 9000, 'node' => 'pve', 'template' => 1, 'type' => 'qemu'], ['scsi0' => 'local-lvm:base-9000-disk-0,size=3584M'], 'Template 9000 has no cloud-init drive.'],
]);

test('proxmox plans larger than the node are unavailable', function () {
    $this->actingAs($this->user);

    Http::fake([
        '*/api2/json/nodes' => Http::response(['data' => [
            ['node' => 'pve', 'status' => 'online', 'maxcpu' => 4, 'maxmem' => 8 * 1024 ** 3 - 1],
        ]]),
    ]);

    $plans = $this->get(route('server-providers.plans', [
        'serverProvider' => $this->proxmox->id,
        'region' => 'pve',
    ]))
        ->assertSuccessful()
        ->json();

    expect(collect($plans)->map(fn (array $plan): bool => $plan['available'])->all())->toBe([
        'xsmall' => true,
        'small' => true,
        'medium' => true,
        'large' => false,
        'xlarge' => false,
        '2xlarge' => false,
    ]);
});

test('create proxmox server clones the mapped template', function () {
    $this->actingAs($this->user);

    Queue::fake();
    Http::fake([
        '*/api2/json/cluster/resources*' => Http::response(['data' => [
            ['vmid' => 9000, 'node' => 'pve', 'template' => 1, 'type' => 'qemu'],
        ]]),
        '*/api2/json/cluster/nextid' => Http::response(['data' => '102']),
        '*/api2/json/nodes/pve/qemu/9000/clone' => Http::response(['data' => 'UPID:pve:0001:qmclone:9000:vito@pve!vito:']),
    ]);

    $this->post(route('servers.store'), [
        'provider' => Proxmox::id(),
        'server_provider' => $this->proxmox->id,
        'name' => 'Web 1',
        'os' => OperatingSystem::UBUNTU24->value,
        'region' => 'pve2',
        'plan' => 'medium',
        'static_ip' => '192.168.1.50/24',
        'gateway' => '192.168.1.1',
    ])->assertSessionDoesntHaveErrors();

    $server = Server::query()->where('name', 'Web 1')->firstOrFail();

    expect($server->ip)->toBe('192.168.1.50')
        ->and($server->ssh_user)->toBe('root')
        ->and($server->provider_data)->toMatchArray([
            'region' => 'pve2',
            'plan' => 'medium',
            'vmid' => 102,
            'task' => 'UPID:pve:0001:qmclone:9000:vito@pve!vito:',
        ]);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/nodes/pve/qemu/9000/clone')
        && $request['newid'] == 102
        && $request['name'] === 'web-1'
        && $request['description'] === 'Managed by Vito (server #'.$server->id.')'
        && $request['full'] == 1
        && $request['target'] === 'pve2');

    Queue::assertPushed(InstallJob::class);
});

test('cannot create proxmox server with invalid input', function (array $input, string $error) {
    $this->actingAs($this->user);

    Http::fake();

    $this->post(route('servers.store'), array_merge([
        'provider' => Proxmox::id(),
        'server_provider' => $this->proxmox->id,
        'name' => 'web',
        'os' => OperatingSystem::UBUNTU24->value,
        'region' => 'pve',
        'plan' => 'medium',
    ], $input))->assertSessionHasErrors($error);

    Http::assertNothingSent();
})->with([
    'os without template' => [['os' => OperatingSystem::UBUNTU22->value], 'provider'],
    'ip without prefix' => [['static_ip' => '192.168.1.50', 'gateway' => '192.168.1.1'], 'static_ip'],
    'restricted ip' => [['static_ip' => '127.0.0.1/8', 'gateway' => '127.0.0.254'], 'static_ip'],
    'ip without gateway' => [['static_ip' => '192.168.1.50/24'], 'gateway'],
    'unknown plan' => [['plan' => 'huge'], 'plan'],
    'unsafe node' => [['region' => '../../access'], 'region'],
]);

test('proxmox configures and starts the vm once the clone finishes', function (array $providerData, string $ipConfig, bool $agent) {
    $this->server->update([
        'provider_data' => array_merge($this->server->provider_data, $providerData, [
            'task' => 'UPID:pve:0001:qmclone:9000:vito@pve!vito:',
        ]),
    ]);

    Http::fake([
        '*/api2/json/nodes/pve/tasks/*' => Http::response(['data' => ['status' => 'stopped', 'exitstatus' => 'OK']]),
        '*/api2/json/nodes/pve/qemu/101/config' => fn (Request $request) => $request->method() === 'GET'
            ? Http::response(['data' => [
                'boot' => 'order=scsi0;ide2;net0',
                'ide2' => 'local-lvm:vm-101-cloudinit,media=cdrom',
                'scsi0' => 'local-lvm:vm-101-disk-0,size=3584M',
            ]])
            : Http::response(['data' => null]),
        '*/api2/json/nodes/pve/qemu/101/resize' => Http::response(['data' => 'UPID:pve:0002:resize:101:vito@pve!vito:']),
        '*/api2/json/nodes/pve/qemu/101/status/current' => Http::response(['data' => ['status' => 'stopped']]),
        '*/api2/json/nodes/pve/qemu/101/status/start' => Http::response(['data' => 'UPID:pve:0003:qmstart:101:vito@pve!vito:']),
    ]);

    expect($this->server->provider()->isRunning())->toBeFalse()
        ->and($this->server->refresh()->provider_data)->not->toHaveKey('task');

    $publicKey = rawurlencode(trim($this->server->sshKey()['public_key']));

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && str_ends_with($request->url(), '/qemu/101/config')
        && $request['cores'] == 2
        && $request['memory'] == 4096
        && $request['ciuser'] === 'root'
        && $request['sshkeys'] === $publicKey
        && $request['ipconfig0'] === $ipConfig
        && $request['ciupgrade'] == 0
        && isset($request['agent']) === $agent);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/qemu/101/resize')
        && $request['disk'] === 'scsi0'
        && $request['size'] === '80G');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_ends_with($request->url(), '/qemu/101/status/start'));
})->with([
    'dhcp' => [[], 'ip=dhcp', true],
    'static ip' => [['static_ip' => '192.168.1.50/24', 'gateway' => '192.168.1.1'], 'ip=192.168.1.50/24,gw=192.168.1.1', false],
]);

test('proxmox does not start the vm again when provisioning is retried', function () {
    $this->server->update([
        'provider_data' => array_merge($this->server->provider_data, [
            'task' => 'UPID:pve:0001:qmclone:9000:vito@pve!vito:',
        ]),
    ]);

    Http::fake([
        '*/api2/json/nodes/pve/tasks/*' => Http::response(['data' => ['status' => 'stopped', 'exitstatus' => 'OK']]),
        '*/api2/json/nodes/pve/qemu/101/config' => fn (Request $request) => $request->method() === 'GET'
            ? Http::response(['data' => ['scsi0' => 'local-lvm:vm-101-disk-0,size=80G']])
            : Http::response(['data' => null]),
        '*/api2/json/nodes/pve/qemu/101/status/current' => Http::response(['data' => ['status' => 'running']]),
        '*' => Http::response(['data' => null]),
    ]);

    expect($this->server->provider()->isRunning())->toBeFalse()
        ->and($this->server->refresh()->provider_data)->not->toHaveKey('task');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && str_ends_with($request->url(), '/qemu/101/config')
        && $request['ipconfig0'] === 'ip=dhcp');
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/status/start')
        || str_ends_with($request->url(), '/resize'));
});

test('proxmox fails the install when the clone task fails', function () {
    $this->server->update([
        'provider_data' => array_merge($this->server->provider_data, [
            'task' => 'UPID:pve:0001:qmclone:9000:vito@pve!vito:',
        ]),
    ]);

    Http::fake([
        '*/api2/json/nodes/pve/tasks/*' => Http::response(['data' => ['status' => 'stopped', 'exitstatus' => "can't lock file"]]),
    ]);

    expect(fn () => $this->server->provider()->isRunning())
        ->toThrow(ServerProviderError::class, "Proxmox task failed: can't lock file");
});

test('proxmox reads the dhcp ip from the guest agent', function () {
    $this->server->update(['ip' => '']);

    Http::fake([
        '*/api2/json/nodes/pve/qemu/101/status/current' => Http::response(['data' => ['status' => 'running']]),
        '*/api2/json/nodes/pve/qemu/101/agent/network-get-interfaces' => Http::sequence()
            ->push(['data' => null], 500)
            ->push(['data' => ['result' => [
                ['name' => 'lo', 'ip-addresses' => [['ip-address-type' => 'ipv4', 'ip-address' => '127.0.0.1']]],
                ['name' => 'eth0', 'ip-addresses' => [
                    ['ip-address-type' => 'ipv6', 'ip-address' => 'fe80::1'],
                    ['ip-address-type' => 'ipv4', 'ip-address' => '192.168.1.77'],
                ]],
            ]]]),
    ]);

    expect($this->server->provider()->isRunning())->toBeFalse()
        ->and($this->server->provider()->isRunning())->toBeTrue()
        ->and($this->server->refresh()->ip)->toBe('192.168.1.77');
});

test('proxmox ignores a restricted ip reported by the guest agent', function () {
    config()->set('core.restricted_ip_addresses', ['192.168.1.77']);
    $this->server->update(['ip' => '']);

    Http::fake([
        '*/api2/json/nodes/pve/qemu/101/status/current' => Http::response(['data' => ['status' => 'running']]),
        '*/api2/json/nodes/pve/qemu/101/agent/network-get-interfaces' => Http::response(['data' => ['result' => [
            ['name' => 'eth0', 'ip-addresses' => [['ip-address-type' => 'ipv4', 'ip-address' => '192.168.1.77']]],
        ]]]),
    ]);

    expect($this->server->provider()->isRunning())->toBeFalse()
        ->and($this->server->refresh()->ip)->toBe('');
});

test('delete proxmox server stops and destroys the vm', function () {
    Http::fake([
        '*/api2/json/nodes/pve/qemu/101/config' => Http::response(['data' => [
            'description' => 'Managed by Vito (server #'.$this->server->id.')',
        ]]),
        '*/api2/json/nodes/pve/qemu/101/status/current' => Http::response(['data' => ['status' => 'running']]),
        '*/api2/json/nodes/pve/qemu/101/status/stop' => Http::response(['data' => 'UPID:pve:0004:qmstop:101:vito@pve!vito:']),
        '*/api2/json/nodes/pve/tasks/*' => Http::response(['data' => ['status' => 'stopped', 'exitstatus' => 'OK']]),
        '*/api2/json/nodes/pve/qemu/101?*' => Http::response(['data' => 'UPID:pve:0005:qmdestroy:101:vito@pve!vito:']),
    ]);

    $this->server->provider()->delete();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_ends_with($request->url(), '/qemu/101/status/stop'));
    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && str_contains($request->url(), '/qemu/101?')
        && $request['purge'] == 1
        && $request['destroy-unreferenced-disks'] == 1);
});

test('delete proxmox server leaves a vm it did not create untouched', function () {
    Http::fake([
        '*/api2/json/nodes/pve/qemu/101/config' => Http::response(['data' => [
            'description' => 'Managed by Vito (server #'.($this->server->id + 1).')',
        ]]),
        '*' => Http::response(['data' => null]),
    ]);

    $this->server->provider()->delete();

    Http::assertNotSent(fn (Request $request): bool => in_array($request->method(), ['POST', 'DELETE'], true));
});
