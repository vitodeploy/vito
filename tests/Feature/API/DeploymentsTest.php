<?php

use App\Enums\DeploymentStatus;
use App\Facades\SSH;
use App\Jobs\Site\DeployJob;
use App\Models\Deployment;
use App\Models\Site;
use App\SiteTypes\Laravel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\PrepareLoadBalancer;

uses(PrepareLoadBalancer::class, RefreshDatabase::class);

beforeEach(function () {
    $this->prepare();
});

test('see deployments list', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.deployments', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful();
});

test('see deployment', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Deployment $deployment */
    $deployment = Deployment::factory()->create([
        'site_id' => $site->id,
        'deployment_script_id' => $site->deploymentScript?->id,
        'status' => DeploymentStatus::FINISHED,
    ]);

    $this->json('GET', route('api.projects.servers.sites.deployments.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
        'deployment' => $deployment,
    ]))
        ->assertSuccessful();
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

test('deployment API resolves the requested commit without changing the site branch', function (string $endpoint) {
    Queue::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);
    $commit = str_repeat('a', 40);
    Http::fake([
        'https://api.github.com/repos/organization/repository/commits/'.$commit => Http::response([
            'sha' => $commit,
            'commit' => ['message' => 'Published release'],
        ]),
        'https://api.github.com/repos/organization/repository' => Http::response([]),
    ]);

    $this->postJson(route($endpoint, [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), ['commit_hash' => strtoupper($commit)])
        ->assertSuccessful()
        ->assertJsonPath('commit_id', $commit)
        ->assertJsonPath('commit_data.message', 'Published release');

    $this->assertDatabaseHas('deployments', ['site_id' => $this->site->id, 'commit_id' => $commit]);
    expect($this->site->fresh()->branch)->toBe('main');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/organization/repository/commits/'.$commit);
    Queue::assertPushed(DeployJob::class);
})->with(['api.projects.servers.sites.deploy', 'api.projects.servers.sites.deployments.store']);

test('deployment API still resolves the configured branch when no commit is supplied', function () {
    Queue::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);
    $commit = str_repeat('b', 40);
    Http::fake([
        'https://api.github.com/repos/organization/repository/commits/main' => Http::response([
            'sha' => $commit,
            'commit' => ['message' => 'Branch head'],
        ]),
        'https://api.github.com/repos/organization/repository' => Http::response([]),
    ]);

    $this->postJson(route('api.projects.servers.sites.deployments.store', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertSuccessful()->assertJsonPath('commit_id', $commit);

    Queue::assertPushed(DeployJob::class);
});

test('deployment API rejects invalid commit input before creating a deployment', function (mixed $commit) {
    Queue::fake();
    Http::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->postJson(route('api.projects.servers.sites.deployments.store', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), ['commit_hash' => $commit])->assertUnprocessable()->assertJsonValidationErrors('commit_hash');

    $this->assertDatabaseCount('deployments', 0);
    $this->assertDatabaseCount('server_logs', 0);
    Queue::assertNothingPushed();
    Http::assertNothingSent();
})->with([null, '', 'main', 'v1.2.3', 'abc123', str_repeat('g', 40), str_repeat('a', 40).';id', 123, [['sha' => 'abc']]]);

test('deployment API rejects an unresolved or different commit without falling back to the branch', function (array $response) {
    Queue::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);
    Http::fake([
        'https://api.github.com/repos/organization/repository/commits/*' => Http::response($response),
        'https://api.github.com/repos/organization/repository' => Http::response([]),
    ]);

    $this->postJson(route('api.projects.servers.sites.deployments.store', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), ['commit_hash' => str_repeat('a', 40)])->assertUnprocessable()->assertJsonValidationErrors('commit_hash');

    $this->assertDatabaseCount('deployments', 0);
    $this->assertDatabaseCount('server_logs', 0);
    Queue::assertNothingPushed();
})->with([
    'unresolved' => [[]],
    'different commit' => [['sha' => str_repeat('b', 40), 'commit' => ['message' => 'Wrong commit']]],
]);

test('deployment API distinguishes unknown commits from provider failures', function (int $providerStatus, int $expectedStatus) {
    Queue::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);
    Http::fake([
        'https://api.github.com/repos/organization/repository/commits/*' => Http::response([], $providerStatus),
        'https://api.github.com/repos/organization/repository' => Http::response([]),
    ]);

    $response = $this->postJson(route('api.projects.servers.sites.deployments.store', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), ['commit_hash' => str_repeat('a', 40)]);
    $response->assertStatus($expectedStatus);
    if ($providerStatus === 404) {
        $response->assertJsonValidationErrors('commit_hash');
    }

    $this->assertDatabaseCount('deployments', 0);
    $this->assertDatabaseCount('server_logs', 0);
    Queue::assertNothingPushed();
})->with(['unknown commit' => [404, 422], 'provider permission denied' => [403, 500]]);

test('deployment API requires source control for an explicit commit', function () {
    Queue::fake();
    Http::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);
    $this->site->update(['source_control_id' => null]);

    $this->postJson(route('api.projects.servers.sites.deployments.store', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), ['commit_hash' => str_repeat('a', 40)])->assertUnprocessable()->assertJsonValidationErrors('commit_hash');

    $this->assertDatabaseCount('deployments', 0);
    Queue::assertNothingPushed();
    Http::assertNothingSent();
});

test('commit deployments require write access', function (string $endpoint) {
    Queue::fake();
    Http::fake();
    Sanctum::actingAs($this->user, ['read']);

    $this->postJson(route($endpoint, [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), ['commit_hash' => str_repeat('a', 40)])->assertForbidden();

    $this->assertDatabaseCount('deployments', 0);
    Queue::assertNothingPushed();
    Http::assertNothingSent();
})->with(['api.projects.servers.sites.deploy', 'api.projects.servers.sites.deployments.store']);

test('deployment scripts receive the pinned commit and modern builds check it out first', function (bool $modern) {
    SSH::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);
    $commit = str_repeat('a', 40);
    $this->site->update(['type' => Laravel::id(), 'type_data' => ['modern_deployment' => $modern]]);
    $this->site->deploymentScript->update(['content' => 'echo "Deploying $COMMIT_ID"']);
    if ($modern) {
        $this->site->buildScript()->create(['name' => 'build', 'content' => 'echo build-release-marker']);
    }
    Http::fake([
        'https://api.github.com/repos/organization/repository/commits/'.$commit => Http::response([
            'sha' => $commit,
            'commit' => ['message' => 'Release'],
        ]),
        'https://api.github.com/repos/organization/repository' => Http::response([]),
    ]);

    $this->postJson(route('api.projects.servers.sites.deployments.store', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]), ['commit_hash' => $commit])->assertSuccessful()->assertJsonPath('commit_id', $commit);

    $this->assertDatabaseHas('deployments', [
        'site_id' => $this->site->id,
        'commit_id' => $commit,
        'status' => DeploymentStatus::FINISHED,
    ]);
    SSH::assertExecutedContains("export COMMIT_ID='".$commit."'");
    if ($modern) {
        SSH::assertExecutedContains("git fetch origin '".$commit."'");
        SSH::assertExecutedContains("git checkout -f '".$commit."'");
        $commands = collect(SSH::getExecutedCommands());
        $fetchIndex = $commands->search(fn (string $command) => str_contains($command, "git fetch origin '".$commit."'"));
        $checkoutIndex = $commands->search(fn (string $command) => str_contains($command, "git checkout -f '".$commit."'"));
        $buildIndex = $commands->search(fn (string $command) => str_contains($command, 'echo build-release-marker'));
        expect($fetchIndex)->not->toBeFalse();
        expect($checkoutIndex)->not->toBeFalse();
        expect($buildIndex)->not->toBeFalse();
        expect($fetchIndex)->toBeLessThan($checkoutIndex);
        expect($checkoutIndex)->toBeLessThan($buildIndex);
    }
})->with([false, true]);


test('modern deployments without a requested commit keep the branch checkout', function (bool $legacyJob) {
    SSH::fake();
    Queue::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);
    $this->site->update(['type' => Laravel::id(), 'type_data' => ['modern_deployment' => true]]);
    Http::fake([
        'https://api.github.com/repos/organization/repository/commits/main' => Http::response([
            'sha' => str_repeat('b', 40),
            'commit' => ['message' => 'Cached branch metadata'],
        ]),
        'https://api.github.com/repos/organization/repository' => Http::response([]),
    ]);

    $this->postJson(route('api.projects.servers.sites.deployments.store', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertSuccessful();

    /** @var DeployJob $job */
    $job = Queue::pushed(DeployJob::class)->first();
    if ($legacyJob) {
        (function () {
            unset($this->checkoutCommit);
        })->call($job);
    }
    $job->handle();

    $this->assertDatabaseHas('deployments', ['site_id' => $this->site->id, 'status' => DeploymentStatus::FINISHED]);
    SSH::assertExecutedContains("clone -b 'main'");
    SSH::assertNotExecutedContains('git checkout -f');
})->with([false, true]);
