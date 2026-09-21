<?php

use App\Actions\Bootstrap\GetBootstrap;
use App\Actions\Server\CreateServer;
use App\Actions\Server\DeleteServer;
use App\Enums\OperatingSystem;
use App\Exceptions\ServerProviderError;
use App\Facades\SSH;
use App\Jobs\Server\InstallJob;
use App\Models\Server;
use App\Models\ServerProvider;
use App\ServerProviders\Lightsail;
use Aws\Command;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\Lightsail\LightsailClient;
use Aws\MockHandler;
use Aws\Result;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Psr\Http\Message\RequestInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    SSH::fake();
    Queue::fake();
    $this->lightsailHandler = new MockHandler;
    $this->lightsailCommands = [];
    $this->app->bind(LightsailClient::class, function ($app, array $parameters): LightsailClient {
        return new LightsailClient(array_merge($parameters['args'], [
            'retries' => 0,
            'handler' => function (CommandInterface $command, RequestInterface $request): PromiseInterface {
                $this->lightsailCommands[] = [
                    'name' => $command->getName(),
                    'parameters' => $command->toArray(),
                    'host' => $request->getUri()->getHost(),
                ];

                return ($this->lightsailHandler)($command, $request);
            },
        ]));
    });

    $this->lightsailProfile = ServerProvider::factory()->create([
        'provider' => Lightsail::id(),
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
    ]);
    $this->server->update([
        'provider' => Lightsail::id(),
        'provider_id' => $this->lightsailProfile->id,
        'provider_data' => ['region' => 'eu-central-1', 'plan' => 'small_3_0'],
        'os' => OperatingSystem::UBUNTU24,
        'ip' => '',
    ]);
    $this->server->refresh();
    $this->lightsailBundle = [
        'bundleId' => 'small_3_0', 'name' => 'Small', 'isActive' => true,
        'supportedPlatforms' => ['LINUX_UNIX'], 'publicIpv4AddressCount' => 1,
        'cpuCount' => 2, 'ramSizeInGb' => 2, 'diskSizeInGb' => 60, 'price' => 12,
    ];
    $this->lightsailRegions = ['regions' => [[
        'name' => 'eu-central-1', 'displayName' => 'Frankfurt',
        'availabilityZones' => [['zoneName' => 'eu-central-1b']],
    ]]];
    $this->lightsailBlueprint = [
        'blueprintId' => 'ubuntu_24_04', 'group' => 'ubuntu', 'type' => 'os',
        'isActive' => true, 'version' => '24.04 LTS',
    ];
});

test('lightsail is available through bootstrap with a masked secret field', function () {
    $config = app(GetBootstrap::class)->handle()['configs']['server_provider']['providers']['lightsail'];

    expect($config['label'])->toBe('AWS Lightsail')
        ->and($config['default_user'])->toBe('ubuntu')
        ->and($config['form'][1]['type'])->toBe('password');
});

test('connect lightsail through the existing web and api flows', function (bool $api) {
    $this->lightsailHandler->append(new Result($this->lightsailRegions));
    $input = ['provider' => 'lightsail', 'name' => 'My Lightsail', 'key' => 'new-key', 'secret' => 'new-secret'];

    if ($api) {
        Sanctum::actingAs($this->user, ['read', 'write']);
        $this->postJson(route('api.user.server-providers.create'), $input)
            ->assertSuccessful()
            ->assertJsonFragment(['provider' => 'lightsail'])
            ->assertDontSee('new-secret')
            ->assertDontSee('new-key');
    } else {
        $this->actingAs($this->user)->post(route('server-providers.store'), $input)
            ->assertSessionDoesntHaveErrors();
    }

    $this->assertDatabaseHas('server_providers', [
        'profile' => 'My Lightsail', 'provider' => 'lightsail',
        'project_id' => $this->user->current_project_id,
    ]);
    $profile = ServerProvider::query()->where('profile', 'My Lightsail')->firstOrFail();
    expect($profile->credentials)->toBe(['key' => 'new-key', 'secret' => 'new-secret'])
        ->and($profile->getRawOriginal('credentials'))->not->toContain('new-secret')
        ->and($this->lightsailHandler->getLastRequest()->getHeaderLine('Authorization'))->toContain('Credential=new-key/');
})->with([false, true]);

test('lightsail credentials are required and must be strings', function (array $credentials) {
    Sanctum::actingAs($this->user, ['write']);
    $this->postJson(route('api.user.server-providers.create'), array_merge([
        'provider' => 'lightsail', 'name' => 'Invalid',
    ], $credentials))->assertUnprocessable()->assertJsonValidationErrors(['key', 'secret']);

    expect($this->lightsailCommands)->toBe([]);
})->with([[[]], [['key' => [], 'secret' => []]]]);

test('lightsail rejected credentials return validation errors without secrets', function () {
    Sanctum::actingAs($this->user, ['write']);
    $this->lightsailHandler->append(new AwsException('test-secret', new Command('GetRegions'), ['code' => 'AccessDeniedException']));

    $this->postJson(route('api.user.server-providers.create'), [
        'provider' => 'lightsail', 'name' => 'Rejected', 'key' => 'test-key', 'secret' => 'test-secret',
    ])->assertUnprocessable()->assertJsonValidationErrors('provider')->assertDontSee('test-secret');

    $this->assertDatabaseMissing('server_providers', ['profile' => 'Rejected']);
});

test('lightsail regions and paginated compatible plans use the selected region', function () {
    $this->actingAs($this->user);
    $this->lightsailHandler->append(
        new Result($this->lightsailRegions),
        new Result(['bundles' => [
            array_merge($this->lightsailBundle, ['bundleId' => 'windows', 'supportedPlatforms' => ['WINDOWS']]),
            array_merge($this->lightsailBundle, ['bundleId' => 'ipv6', 'publicIpv4AddressCount' => 0]),
            array_merge($this->lightsailBundle, ['bundleId' => 'inactive', 'isActive' => false]),
        ], 'nextPageToken' => 'next-bundles']),
        new Result(['bundles' => [$this->lightsailBundle]]),
    );

    $this->getJson(route('server-providers.regions', $this->lightsailProfile))
        ->assertExactJson(['eu-central-1' => 'Frankfurt (eu-central-1)']);
    $this->getJson(route('server-providers.plans', ['serverProvider' => $this->lightsailProfile, 'region' => 'eu-central-1']))
        ->assertExactJson(['small_3_0' => 'Small - 2 Cores - 2048 Memory - 60 Disk (12.00/mo)']);

    expect($this->lightsailCommands[2]['parameters']['pageToken'])->toBe('next-bundles')
        ->and($this->lightsailCommands[2]['host'])->toBe('lightsail.eu-central-1.amazonaws.com')
        ->and($this->lightsailProfile->provider()->plans(null))->toBe([]);
});

test('lightsail provisions the selected ubuntu image and queues installation', function (string $os, string $version) {
    $this->actingAs($this->user);
    $this->lightsailHandler->append(
        new Result($this->lightsailRegions),
        new Result(['bundles' => [$this->lightsailBundle]]),
        new Result(['blueprints' => [array_merge($this->lightsailBlueprint, ['isActive' => false])], 'nextPageToken' => 'next-images']),
        new Result(['blueprints' => [array_merge($this->lightsailBlueprint, [
            'version' => $version.' LTS', 'blueprintId' => 'ubuntu_'.str_replace('.', '_', $version),
        ])]]),
        new Result,
        new Result(['operations' => [['status' => 'Started']]]),
    );

    $this->post(route('servers.store'), [
        'provider' => 'lightsail', 'server_provider' => $this->lightsailProfile->id,
        'name' => 'Production server / with spaces', 'os' => $os,
        'region' => 'eu-central-1', 'plan' => 'small_3_0',
    ])->assertSessionDoesntHaveErrors();

    $server = Server::query()->where('name', 'Production server / with spaces')->firstOrFail();
    $this->assertDatabaseHas('servers', ['id' => $server->id, 'provider' => 'lightsail', 'ssh_user' => 'ubuntu']);
    expect($server->provider_data['instance_name'])->toMatch('/^vito-\d+-[a-z0-9]{12}$/')
        ->and($server->sshKey()['public_key'])->toStartWith('ssh-rsa ')
        ->and($this->lightsailCommands[0]['parameters']['includeAvailabilityZones'])->toBeTrue()
        ->and($this->lightsailCommands[3]['parameters']['pageToken'])->toBe('next-images')
        ->and(base64_decode($this->lightsailCommands[4]['parameters']['publicKeyBase64']))->toBe($server->sshKey()['public_key']);
    $create = $this->lightsailCommands[5]['parameters'];
    expect($create['instanceNames'])->toBe([$server->provider_data['instance_name']])
        ->and($create['keyPairName'])->toBe($server->provider_data['ssh_key_name'])
        ->and($create['availabilityZone'])->toBe('eu-central-1b')
        ->and($create['blueprintId'])->toBe('ubuntu_'.str_replace('.', '_', $version))
        ->and($create['bundleId'])->toBe('small_3_0')
        ->and($create['ipAddressType'])->toBe('ipv4');
    Queue::assertPushed(InstallJob::class);
})->with([
    ['ubuntu_20', '20.04'], ['ubuntu_22', '22.04'], ['ubuntu_24', '24.04'],
]);

test('lightsail validates server input before making requests', function () {
    $this->actingAs($this->user)->postJson(route('servers.store'), [
        'provider' => 'lightsail', 'server_provider' => $this->lightsailProfile->id,
        'name' => 'Invalid', 'os' => 'ubuntu_24', 'region' => 'https://example.com', 'plan' => [],
    ])->assertUnprocessable()->assertJsonValidationErrors(['region', 'plan']);

    expect($this->lightsailCommands)->toBe([]);
});

test('lightsail rejects malformed api plan regions with validation feedback', function () {
    Sanctum::actingAs($this->user, ['read']);

    $this->getJson(route('api.user.server-providers.plans', [
        'serverProvider' => $this->lightsailProfile->id, 'region' => 'not-a-region',
    ]))->assertUnprocessable()->assertJsonValidationErrors('region');

    expect($this->lightsailCommands)->toBe([]);
});

test('lightsail rejects unavailable catalog selections before creating resources', function (string $missing) {
    $this->lightsailHandler->append(new Result($missing === 'region' ? ['regions' => []] : $this->lightsailRegions));
    if ($missing !== 'region') {
        $this->lightsailHandler->append(new Result(['bundles' => $missing === 'plan' ? [] : [$this->lightsailBundle]]));
    }
    if ($missing === 'image') {
        $this->lightsailHandler->append(new Result(['blueprints' => []]));
    }

    expect(fn () => $this->server->provider()->create())->toThrow(ServerProviderError::class, 'unavailable');
    expect(array_column($this->lightsailCommands, 'name'))->not->toContain('ImportKeyPair', 'CreateInstances');
})->with(['region', 'plan', 'image']);

test('lightsail cleans up its key when instance creation fails', function () {
    $this->lightsailHandler->append(
        new Result($this->lightsailRegions),
        new Result(['bundles' => [$this->lightsailBundle]]),
        new Result(['blueprints' => [$this->lightsailBlueprint]]),
        new Result,
        new AwsException('upstream secret', new Command('CreateInstances'), ['code' => 'AccessDeniedException']),
        new AwsException('not found', new Command('DeleteInstance'), ['code' => 'NotFoundException']),
        new Result,
    );

    $this->actingAs($this->user)->postJson(route('servers.store'), [
        'provider' => 'lightsail', 'server_provider' => $this->lightsailProfile->id,
        'name' => 'Failed Lightsail', 'os' => 'ubuntu_24', 'region' => 'eu-central-1', 'plan' => 'small_3_0',
    ])->assertUnprocessable()->assertJsonValidationErrors('provider')->assertDontSee('upstream secret');

    $this->assertDatabaseMissing('servers', ['name' => 'Failed Lightsail']);
    expect($this->lightsailCommands[5]['name'])->toBe('DeleteInstance')
        ->and($this->lightsailCommands[6]['name'])->toBe('DeleteKeyPair')
        ->and($this->lightsailCommands[6]['parameters']['keyPairName'])->toBe($this->lightsailCommands[3]['parameters']['keyPairName']);
    Queue::assertNotPushed(InstallJob::class);
});

test('lightsail retains cleanup targets after a lost creation response', function (string $operation, bool $cleanupFails) {
    $this->lightsailHandler->append(
        new Result($this->lightsailRegions),
        new Result(['bundles' => [$this->lightsailBundle]]),
        new Result(['blueprints' => [$this->lightsailBlueprint]]),
    );
    if ($operation === 'CreateInstances') {
        $this->lightsailHandler->append(new Result);
    }
    $this->lightsailHandler->append(function (CommandInterface $command) use ($operation): AwsException {
        $server = Server::query()->where('name', 'Lost response')->firstOrFail();
        $this->lostResponseKeys = $server->sshKey();
        $field = $operation === 'CreateInstances' ? 'instance_name' : 'ssh_key_name';
        $name = $operation === 'CreateInstances' ? $command['instanceNames'][0] : $command['keyPairName'];
        expect($server->provider_data[$field])->toBe($name);

        return new AwsException('Response lost', $command, ['connection_error' => true]);
    });
    $this->lightsailHandler->append($cleanupFails
        ? new AwsException('Cleanup unavailable', new Command('DeleteInstance'), ['connection_error' => true])
        : new Result);
    if (! $cleanupFails && $operation === 'CreateInstances') {
        $this->lightsailHandler->append(new Result);
    }

    expect(fn () => app(CreateServer::class)->create($this->user, $this->user->currentProject, [
        'provider' => 'lightsail', 'server_provider' => $this->lightsailProfile->id,
        'name' => 'Lost response', 'os' => 'ubuntu_24', 'region' => 'eu-central-1', 'plan' => 'small_3_0',
    ]))->toThrow($cleanupFails ? ServerProviderError::class : Illuminate\Validation\ValidationException::class);

    $deleteOperation = $operation === 'CreateInstances' ? 'DeleteInstance' : 'DeleteKeyPair';
    expect(array_column($this->lightsailCommands, 'name'))->toContain($deleteOperation);
    if ($cleanupFails) {
        $this->assertDatabaseHas('servers', ['name' => 'Lost response']);
        expect(file_exists($this->lostResponseKeys['private_key_path']))->toBeTrue()
            ->and(file_exists($this->lostResponseKeys['public_key_path']))->toBeTrue();
    } else {
        $this->assertDatabaseMissing('servers', ['name' => 'Lost response']);
    }
    Queue::assertNotPushed(InstallJob::class);
})->with(['ImportKeyPair', 'CreateInstances'])->with([false, true]);

test('lightsail waits for a running instance with a public address', function (array $instance) {
    $this->server->jsonUpdate('provider_data', 'instance_name', 'vito-instance');
    $this->lightsailHandler->append(new Result(['instance' => $instance]));

    expect($this->server->provider()->isRunning())->toBeFalse()
        ->and($this->server->fresh()->ip)->toBe('')
        ->and(array_column($this->lightsailCommands, 'name'))->toBe(['GetInstance']);
})->with([
    [['state' => ['name' => 'pending'], 'publicIpAddress' => '203.0.113.10']],
    [['state' => ['name' => 'running']]],
]);

test('lightsail saves addresses and configures its outer firewall once', function () {
    $this->server->jsonUpdate('provider_data', 'instance_name', 'vito-instance');
    $instance = ['state' => ['name' => 'running'], 'publicIpAddress' => '203.0.113.10', 'privateIpAddress' => '172.26.1.10'];
    $this->lightsailHandler->append(new Result(['instance' => $instance]), new Result, new Result(['instance' => $instance]));

    expect($this->server->provider()->isRunning())->toBeTrue()
        ->and($this->server->fresh()->provider()->isRunning())->toBeTrue();
    $this->assertDatabaseHas('servers', ['id' => $this->server->id, 'ip' => '203.0.113.10', 'local_ip' => '172.26.1.10']);
    expect($this->lightsailCommands[1]['parameters']['portInfos'])->toBe([[
        'fromPort' => 0, 'toPort' => 65535, 'protocol' => 'all', 'cidrs' => ['0.0.0.0/0'],
    ]])->and(array_column($this->lightsailCommands, 'name'))->toBe(['GetInstance', 'PutInstancePublicPorts', 'GetInstance']);
});

test('lightsail readiness handles resources not yet visible', function () {
    expect($this->server->provider()->isRunning())->toBeFalse();
    $this->server->jsonUpdate('provider_data', 'instance_name', 'vito-instance');
    $this->lightsailHandler->append(new AwsException('not found', new Command('GetInstance'), ['code' => 'NotFoundException']));

    expect($this->server->provider()->isRunning())->toBeFalse();
});

test('lightsail deletion respects the existing delete from provider choice', function (bool $delete) {
    $this->server->jsonUpdate('provider_data', 'instance_name', 'vito-instance');
    $this->server->jsonUpdate('provider_data', 'ssh_key_name', 'vito-key');
    if ($delete) {
        $this->lightsailHandler->append(new Result, new Result);
    }

    $this->actingAs($this->user)->delete(route('servers.destroy', $this->server), [
        'name' => $this->server->name, 'delete_from_provider' => $delete,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('servers', ['id' => $this->server->id]);
    expect(array_column($this->lightsailCommands, 'name'))->toBe($delete ? ['DeleteInstance', 'DeleteKeyPair'] : []);
    if ($delete) {
        expect($this->lightsailCommands[0]['parameters']['instanceName'])->toBe('vito-instance')
            ->and($this->lightsailCommands[1]['parameters']['keyPairName'])->toBe('vito-key');
    }
})->with([true, false]);

test('lightsail deletion tolerates resources already removed in aws', function () {
    $this->server->jsonUpdate('provider_data', 'instance_name', 'vito-instance');
    $this->server->jsonUpdate('provider_data', 'ssh_key_name', 'vito-key');
    $this->lightsailHandler->append(
        new AwsException('not found', new Command('DeleteInstance'), ['code' => 'NotFoundException']),
        new AwsException('not found', new Command('DeleteKeyPair'), ['code' => 'NotFoundException']),
    );

    $this->server->provider()->delete();

    expect(array_column($this->lightsailCommands, 'name'))->toBe(['DeleteInstance', 'DeleteKeyPair']);
});

test('lightsail keeps the server and ssh keys when aws rejects deletion', function () {
    $this->server->jsonUpdate('provider_data', 'instance_name', 'vito-instance');
    $keys = $this->server->sshKey();
    $this->lightsailHandler->append(new AwsException('denied', new Command('DeleteInstance'), ['code' => 'AccessDeniedException']));

    expect(fn () => app(DeleteServer::class)->delete($this->server, [
        'name' => $this->server->name, 'delete_from_provider' => true,
    ]))->toThrow(ServerProviderError::class);

    $this->assertDatabaseHas('servers', ['id' => $this->server->id]);
    expect(file_exists($keys['private_key_path']))->toBeTrue()
        ->and(file_exists($keys['public_key_path']))->toBeTrue();
});

test('lightsail surfaces upstream failures without retaining credential bearing exceptions', function (bool $operationFailure) {
    $this->server->jsonUpdate('provider_data', 'instance_name', 'vito-instance');
    $this->lightsailHandler->append($operationFailure
        ? new Result(['operations' => [['status' => 'Failed', 'errorDetails' => 'test-secret']]])
        : new AwsException('test-secret', new Command('DeleteInstance'), ['code' => 'AccessDeniedException']));

    try {
        $this->server->provider()->delete();
        $this->fail('Expected the provider error to be surfaced.');
    } catch (ServerProviderError $exception) {
        expect($exception->getMessage())->toContain('DeleteInstance')->not->toContain('test-secret')
            ->and($exception->getPrevious())->toBeNull();
    }
})->with([true, false]);
