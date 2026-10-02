<?php

use App\Enums\DeploymentStatus;
use App\Enums\LoadBalancerMethod;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Jobs\Site\CreateJob;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use App\SiteTypes\Blank;
use App\SiteTypes\Wordpress;
use App\SourceControlProviders\Github;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\PrepareLoadBalancer;

uses(PrepareLoadBalancer::class);

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->prepare();
});

test('api blank sites discard disabled source control input', function (?bool $enabled) {
    SSH::fake();
    Http::fake();
    Queue::fake();

    $sourceControl = SourceControl::factory()->create(['user_id' => User::factory()->create()->id]);
    $token = $this->user->createToken('site-create', ['write', 'project:'.$this->server->project_id]);
    $input = [
        'type' => Blank::id(),
        'domain' => 'blank-disabled.example.com',
        'port' => 3000,
        'user' => 'blankdisabled',
        'source_control' => $sourceControl->id,
        'repository' => 'private/repository',
        'branch' => 'private',
    ];
    if ($enabled !== null) {
        $input['use_source_control'] = $enabled;
    }

    $this->withToken($token->plainTextToken)->postJson(route('api.projects.servers.sites.create', [
        'project' => $this->server->project_id,
        'server' => $this->server,
    ]), $input)->assertSuccessful()->assertJsonPath('source_control_id', null);

    $this->assertDatabaseHas('sites', [
        'domain' => $input['domain'],
        'source_control_id' => null,
        'repository' => '',
        'branch' => '',
    ]);
    Queue::assertPushed(CreateJob::class, 1);
    Http::assertNothingSent();
})->with([false, null]);

test('api site source selection uses the token actor rather than the server creator', function (string $scope) {
    SSH::fake();
    Http::fake();
    Queue::fake();

    $creator = User::factory()->create();
    $this->server->update(['user_id' => $creator->id]);
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::ADMIN]);
    $sourceControl = SourceControl::factory()->create([
        'user_id' => $scope === 'creator' ? $creator->id : $this->user->id,
        'project_id' => match ($scope) {
            'project' => $this->server->project_id,
            'foreign-project' => Project::factory()->create()->id,
            default => null,
        },
    ]);
    $token = $this->user->createToken('site-create', ['write', 'project:'.$this->server->project_id]);

    $response = $this->withToken($token->plainTextToken)->postJson(route('api.projects.servers.sites.create', [
        'project' => $this->server->project_id,
        'server' => $this->server,
    ]), [
        'type' => Blank::id(),
        'domain' => 'actor-source.example.com',
        'port' => 3000,
        'user' => 'actorsource',
        'use_source_control' => true,
        'source_control' => $sourceControl->id,
        'repository' => 'private/repository',
        'branch' => 'main',
    ]);

    if (in_array($scope, ['creator', 'foreign-project'])) {
        if ($scope === 'creator') {
            $response->assertForbidden();
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('source_control');
        }
        $this->assertDatabaseMissing('sites', ['domain' => 'actor-source.example.com']);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    } else {
        $response->assertSuccessful()->assertJsonPath('source_control_id', $sourceControl->id);
        $this->assertDatabaseHas('sites', ['source_control_id' => $sourceControl->id]);
        Http::assertSentCount(1);
    }
})->with(['creator', 'global', 'project', 'foreign-project']);

test('api site resources hide installation passwords with persisted read tokens', function (UserRole $role) {
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => $role]);
    $typeData = [
        'title' => 'Private WordPress',
        'password' => 'wordpress-install-secret',
        'database_password' => 'database-install-secret',
    ];
    $this->site->update(['type' => Wordpress::id(), 'type_data' => $typeData]);
    $token = $this->user->createToken('read-sites', ['read', 'project:'.$this->server->project_id]);

    $this->withToken($token->plainTextToken)->getJson(route('api.projects.servers.sites.show', [
        'project' => $this->server->project_id,
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertSuccessful()
        ->assertJsonPath('type_data.title', $typeData['title'])
        ->assertJsonMissingPath('type_data.password')
        ->assertJsonMissingPath('type_data.database_password');

    $this->getJson(route('api.projects.servers.sites', [
        'project' => $this->server->project_id,
        'server' => $this->server,
    ]))->assertSuccessful()
        ->assertJsonMissingPath('data.0.type_data.password')
        ->assertJsonMissingPath('data.0.type_data.database_password');

    expect($this->site->fresh()->type_data)->toBe($typeData);
})->with([UserRole::OWNER, UserRole::USER]);

test('create site', function (array $inputs) {
    SSH::fake();

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
        ], 201),
    ]);

    Sanctum::actingAs($this->user, ['read', 'write']);

    if (isset($inputs['database']) && isset($inputs['database_user'])) {
        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server->id,
        ]);
        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server->id,
        ]);
        $inputs['database'] = $database->id;
        $inputs['database_user'] = $databaseUser->id;
    }

    /** @var SourceControl $sourceControl */
    $sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);

    $inputs['source_control'] = $sourceControl->id;

    $this->json('POST', route('api.projects.servers.sites.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), $inputs)
        ->assertSuccessful()
        ->assertJsonFragment([
            'domain' => $inputs['domain'],
            'user' => $inputs['user'],
            'path' => '/home/'.$inputs['user'].'/'.$inputs['domain'],
        ]);
})->with('create_data');

test('see sites list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'domain' => $site->domain,
        ]);
});

test('see site', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'domain' => $site->domain,
        ]);
});

test('delete site', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('DELETE', route('api.projects.servers.sites.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
});

test('update web directory', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('PUT', route('api.projects.servers.sites.web-directory', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [
        'web_directory' => 'public',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'web_directory' => 'public',
        ]);
});

test('update load balancer', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $servers = Server::query()->where('id', '!=', $this->server->id)->get();
    expect($servers->count())->toEqual(2);

    $this->json('POST', route('api.projects.servers.sites.load-balancer', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'method' => LoadBalancerMethod::ROUND_ROBIN,
        'servers' => [
            [
                'ip' => $servers[0]->local_ip,
                'port' => 80,
                'weight' => 1,
                'backup' => false,
            ],
            [
                'ip' => $servers[1]->local_ip,
                'port' => 80,
                'weight' => 1,
                'backup' => false,
            ],
        ],
    ])
        ->assertSuccessful();

    $this->assertDatabaseHas('load_balancer_servers', [
        'load_balancer_id' => $this->site->id,
        'ip' => $servers[0]->local_ip,
        'port' => 80,
        'weight' => 1,
        'backup' => false,
    ]);
    $this->assertDatabaseHas('load_balancer_servers', [
        'load_balancer_id' => $this->site->id,
        'ip' => $servers[1]->local_ip,
        'port' => 80,
        'weight' => 1,
        'backup' => false,
    ]);
});

test('deploy site', function () {
    SSH::fake();

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
            'commit' => [
                'sha' => 'abc123',
                'commit' => [
                    'message' => 'Test commit',
                    'author' => [
                        'name' => 'Test Author',
                        'email' => 'test@example.com',
                        'date' => now()->toIso8601String(),
                    ],
                ],
            ],
        ], 200),
    ]);

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);
    $site->deploymentScript?->update([
        'content' => 'ls -la',
    ]);

    $this->json('POST', route('api.projects.servers.sites.deploy', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful()
        ->assertJsonStructure([
            'id',
            'status',
        ]);

    $this->assertDatabaseHas('deployments', [
        'site_id' => $site->id,
        'status' => DeploymentStatus::FINISHED,
    ]);
});

test('update deployment script', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $scriptContent = "git pull\ncomposer install\nphp artisan migrate";

    $this->json('PUT', route('api.projects.servers.sites.deployment-script', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [
        'script' => $scriptContent,
    ])
        ->assertSuccessful()
        ->assertNoContent();

    $this->assertDatabaseHas('deployment_scripts', [
        'site_id' => $site->id,
        'content' => $scriptContent,
    ]);
});

test('update deployment script without content', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('PUT', route('api.projects.servers.sites.deployment-script', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['script']);
});

test('show deployment script', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $scriptContent = "git pull\ncomposer install";

    $site->deploymentScript->update([
        'content' => $scriptContent,
    ]);

    $this->json('GET', route('api.projects.servers.sites.deployment-script.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('script', $scriptContent);
});

test('show env', function () {
    $envContent = "APP_NAME=Laravel\nAPP_ENV=production";
    SSH::fake($envContent);

    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $site->update(['env_variables' => ['DB_PASSWORD']]);

    $this->json('GET', route('api.projects.servers.sites.env.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'variables' => [
                    '*' => ['key', 'value', 'is_secret'],
                ],
            ],
        ])
        ->assertJsonMissingPath('data.env');
});

test('show env with write token', function () {
    $envContent = "APP_NAME=Laravel\nDB_PASSWORD=supersecret";
    SSH::fake($envContent);

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $site->update(['env_variables' => ['DB_PASSWORD']]);

    $response = $this->json('GET', route('api.projects.servers.sites.env.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))->assertSuccessful();

    expect($response->json('data.env'))->toEqual($envContent);

    $secret = collect($response->json('data.variables'))->firstWhere('key', 'DB_PASSWORD');
    expect($secret['is_secret'])->toBeTrue();
    expect($secret['value'])->toEqual('supersecret');
});

test('show env unauthorized', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, []);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.env.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertForbidden();
});

test('update env', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $envContent = "APP_NAME=Laravel\nAPP_ENV=production";

    $this->json('PUT', route('api.projects.servers.sites.env', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [
        'env' => $envContent,
        'path' => '/home/vito/some-path/.env',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'domain' => $site->domain,
        ]);
});

/**
 * @return array<array<array<string, mixed>>>
 */
dataset('create_data', function () {
    return vitoPestSiteCreateData();
});
