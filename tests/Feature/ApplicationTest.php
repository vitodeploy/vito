<?php

use App\Actions\Site\UpdateEnv;
use App\Enums\DeploymentStatus;
use App\Enums\UserRole;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Deployment;
use App\Models\GitHook;
use App\Models\Site;
use App\Models\Worker;
use App\Notifications\DeploymentCompleted;
use App\SiteTypes\Blank;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('visit application', function () {
    $this->actingAs($this->user);

    $this->get(route('application', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('application/index'));
});

test('application page passes null worker for non proxied site', function () {
    $this->actingAs($this->user);

    $this->get(route('application', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('application/index')
            ->where('worker', null)
        );
});

test('application page passes bootstrap worker for proxied site', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Site $proxied */
    $proxied = Site::factory()->create([
        'server_id' => $this->server->id,
        'type' => NodeSite::id(),
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $proxied->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $proxied->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

    $this->get(route('application', [
        'server' => $this->server,
        'site' => $proxied,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('application/index')
            ->where('worker.id', $worker->id)
            ->where('worker.is_site_bootstrap', true)
        );
});

test('update deployment script', function () {
    $this->actingAs($this->user);

    $this->put(route('application.update-deployment-script', [
        'server' => $this->server,
        'site' => $this->site,
        'deploymentScript' => $this->site->deploymentScript,
    ]), [
        'script' => 'some script',
        'restart_workers' => true,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('deployment_scripts', [
        'site_id' => $this->site->id,
        'name' => 'default',
        'content' => 'some script',
    ]);

    $deploymentScript = $this->site->refresh()->deploymentScript;
    expect($deploymentScript->shouldRestartWorkers())->toBeTrue();
});

test('deploy classic', function () {
    SSH::fake('fake output');
    Http::fake([
        'github.com/*' => Http::response([
            'sha' => '123',
            'commit' => [
                'message' => 'test commit message',
                'name' => 'test commit name',
                'email' => 'test@example.com',
                'url' => 'https://github.com/commit-url',
            ],
        ]),
    ]);
    Notification::fake();

    $this->site->deploymentScript->update([
        'content' => 'git pull',
    ]);

    $this->actingAs($this->user);

    $this->post(route('application.deploy', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('deployments', [
        'site_id' => $this->site->id,
        'status' => DeploymentStatus::FINISHED,
    ]);

    SSH::assertExecutedContains('cd /home/vito/'.$this->site->domain);
    SSH::assertExecutedContains('git pull');

    Notification::assertSentTo($this->notificationChannel, DeploymentCompleted::class);
});

test('deploy broadcasts deployment created', function () {
    vitoPestFeatureApplicationTestAssertBroadcastsDeploymentCreated(function (): void {
        $this->site->deploymentScript->update([
            'content' => 'git pull',
        ]);
    });
});

test('deploy modern broadcasts deployment created', function () {
    vitoPestFeatureApplicationTestAssertBroadcastsDeploymentCreated(function (): void {
        $this->site->update([
            'type_data' => [
                'modern_deployment' => true,
                'modern_deployment_history' => 10,
                'modern_deployment_shared_resources' => ['.env'],
            ],
        ]);
        $this->site->ensureDeploymentScriptsExist();
        $this->site->refresh();
    });
});

function vitoPestFeatureApplicationTestAssertBroadcastsDeploymentCreated(callable $siteSetup): void
{
    SSH::fake('fake output');
    Http::fake([
        'github.com/*' => Http::response([
            'sha' => '123',
            'commit' => [
                'message' => 'test commit message',
                'name' => 'test commit name',
                'email' => 'test@example.com',
                'url' => 'https://github.com/commit-url',
            ],
        ]),
    ]);
    Notification::fake();
    Event::fake([SocketEvent::class]);

    $siteSetup();

    test()->actingAs(test()->user);

    test()->post(route('application.deploy', [
        'server' => test()->server,
        'site' => test()->site,
    ]))
        ->assertSessionDoesntHaveErrors();

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'deployment.created'
            && $event->data->data['site_id'] === test()->site->id
            && $event->data->projectId === test()->server->project_id,
    );
}

test('deploy reverse proxy without port and start command shows error', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Site $proxied */
    $proxied = Site::factory()->create([
        'server_id' => $this->server->id,
        'type' => Blank::id(),
        'port' => null,
        'type_data' => [],
    ]);

    $this->withHeader('X-Inertia', 'true')
        ->post(route('application.deploy', [
            'server' => $this->server,
            'site' => $proxied,
        ]))->assertSessionHas('error');

    $this->assertDatabaseMissing('deployments', [
        'site_id' => $proxied->id,
    ]);
});

test('deploy reverse proxy with port and start command is allowed', function () {
    SSH::fake('fake output');
    Notification::fake();
    $this->actingAs($this->user);

    /** @var Site $proxied */
    $proxied = Site::factory()->create([
        'server_id' => $this->server->id,
        'type' => Blank::id(),
        'port' => 3000,
        'type_data' => ['start_command' => 'node app.js'],
    ]);

    $proxied->deploymentScript->update(['content' => 'echo deploy']);

    $this->post(route('application.deploy', [
        'server' => $this->server,
        'site' => $proxied,
    ]))->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('deployments', [
        'site_id' => $proxied->id,
    ]);
});

test('deploy modern', function () {
    SSH::fake('fake output');
    Http::fake([
        'github.com/*' => Http::response([
            'sha' => '123',
            'commit' => [
                'message' => 'test commit message',
                'name' => 'test commit name',
                'email' => 'test@example.com',
                'url' => 'https://github.com/commit-url',
            ],
        ]),
    ]);
    Notification::fake();

    $this->site->update([
        'type_data' => [
            'modern_deployment' => true,
            'modern_deployment_history' => 10,
            'modern_deployment_shared_resources' => ['.env'],
        ],
    ]);
    $this->site->ensureDeploymentScriptsExist();
    $this->site->refresh();

    $this->site->buildScript->update([
        'content' => 'composer install',
    ]);

    $this->site->preFlightScript->update([
        'content' => 'php artisan migrate --force',
    ]);

    $this->actingAs($this->user);

    $this->post(route('application.deploy', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('deployments', [
        'site_id' => $this->site->id,
        'status' => DeploymentStatus::FINISHED,
    ]);

    /** @var Deployment $lastDeployment */
    $lastDeployment = $this->site->deployments()->latest()->first();

    expect($lastDeployment->release)->not->toBeNull();

    SSH::assertExecutedContains('composer install');

    Notification::assertSentTo($this->notificationChannel, DeploymentCompleted::class);
});

test('rollback', function () {
    SSH::fake('fake output');
    Notification::fake();

    $this->site->update([
        'type_data' => [
            'modern_deployment' => true,
            'modern_deployment_history' => 10,
            'modern_deployment_shared_resources' => ['.env'],
        ],
    ]);

    $this->actingAs($this->user);

    Deployment::factory()->create([
        'site_id' => $this->site->id,
        'status' => DeploymentStatus::FINISHED,
        'active' => true,
        'release' => '20250901000000',
    ]);

    /** @var Deployment $oldRelease */
    $oldRelease = Deployment::factory()->create([
        'site_id' => $this->site->id,
        'status' => DeploymentStatus::FINISHED,
        'active' => false,
        'release' => '20240901000000',
    ]);

    $this->post(route('application.rollback', [
        'server' => $this->server,
        'site' => $this->site,
        'deployment' => $oldRelease->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('deployments', [
        'id' => $oldRelease->id,
        'site_id' => $this->site->id,
        'status' => DeploymentStatus::FINISHED,
        'active' => true,
    ]);

    SSH::assertExecutedContains('ln -sfn');
});

test('enable auto deployment', function () {
    Http::fake([
        'github.com/*' => Http::response([
            'id' => '123',
        ], 201),
    ]);

    $this->actingAs($this->user);

    $this->post(route('application.enable-auto-deployment', [
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertSessionDoesntHaveErrors();

    $this->site->refresh();

    expect($this->site->isAutoDeployment())->toBeTrue();
});

test('delete release', function () {
    SSH::fake('fake output');

    $this->site->update([
        'type_data' => [
            'modern_deployment' => true,
            'modern_deployment_history' => 10,
            'modern_deployment_shared_resources' => ['.env'],
        ],
    ]);

    $this->actingAs($this->user);

    Deployment::factory()->create([
        'site_id' => $this->site->id,
        'status' => DeploymentStatus::FINISHED,
        'active' => true,
        'release' => '20250901000000',
    ]);

    /** @var Deployment $oldRelease */
    $oldRelease = Deployment::factory()->create([
        'site_id' => $this->site->id,
        'status' => DeploymentStatus::FINISHED,
        'active' => false,
        'release' => '20240901000000',
    ]);

    $this->delete(route('application.deployments.destroy', [
        'server' => $this->server,
        'site' => $this->site,
        'deployment' => $oldRelease->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('deployments', [
        'id' => $oldRelease->id,
    ]);

    SSH::assertExecutedContains('rm -rf '.$this->site->basePath().'/releases/20240901000000');
});

test('disable auto deployment', function () {
    Http::fake([
        'api.github.com/repos/organization/repository' => Http::response([
            'id' => '123',
        ], 200),
        'api.github.com/repos/organization/repository/hooks/*' => Http::response([], 204),
    ]);

    $this->actingAs($this->user);

    GitHook::factory()->create([
        'site_id' => $this->site->id,
        'source_control_id' => $this->site->source_control_id,
    ]);

    $this->post(route('application.disable-auto-deployment', [
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertSessionDoesntHaveErrors();

    $this->site->refresh();

    expect($this->site->isAutoDeployment())->toBeFalse();
});

test('disable auto deployment even if hook destroy fails', function () {
    Http::fake([
        'api.github.com/repos/organization/repository' => Http::response([
            'id' => '123',
        ], 200),
        'api.github.com/repos/organization/repository/hooks/*' => Http::response([], 404),
    ]);

    $this->actingAs($this->user);

    GitHook::factory()->create([
        'site_id' => $this->site->id,
        'source_control_id' => $this->site->source_control_id,
    ]);

    $this->post(route('application.disable-auto-deployment', [
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertSessionDoesntHaveErrors();

    $this->site->refresh();

    expect($this->site->isAutoDeployment())->toBeFalse();
});

test('update env file', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_ENV="production"',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();

    expect(data_get($this->site->type_data, 'env_path'))->toEqual($this->site->path.'/.env');
});

test('update env file with path', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_ENV="production"',
        'path' => $this->site->path.'/some-path/.env',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->site->refresh();

    expect(data_get($this->site->type_data, 'env_path'))->toEqual($this->site->path.'/some-path/.env');
});

test('update env blocks path outside site directory', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_ENV="production"',
        'path' => '/home/vito/other-site/.env',
    ])
        ->assertSessionHasErrors('path');
});

test('update env allows stored env path outside site directory', function () {
    SSH::fake();

    $this->site->update([
        'type_data' => array_merge($this->site->type_data ?? [], [
            'env_path' => '/home/vito/other-site/.env',
        ]),
    ]);

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_ENV="production"',
        'path' => '/home/vito/other-site/.env',
    ])
        ->assertSessionDoesntHaveErrors();
});

test('update env blocks path traversal', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_ENV="production"',
        'path' => $this->site->path.'/../../etc/passwd',
    ])
        ->assertSessionHasErrors('path');
});

test('update env file with variables', function () {
    $ssh = SSH::fake();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'APP_ENV', 'value' => 'production'],
            ['key' => 'APP_DEBUG', 'value' => 'false'],
            ['key' => 'DB_PASSWORD', 'value' => 'secret123'],
            ['key' => 'VITE_PAYMENT_METHODS_MOLLIE', 'value' => '["ideal","paybybank","bancontact"]'],
        ],
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertStringContainsString(
        'VITE_PAYMENT_METHODS_MOLLIE=["ideal","paybybank","bancontact"]',
        $ssh->getUploadedContent()
    );

    $this->site->refresh();

    expect(data_get($this->site->type_data, 'env_path'))->toEqual($this->site->path.'/.env');
});

test('get env returns variables', function () {
    SSH::fake('APP_NAME=TestApp'.PHP_EOL.'DB_PASSWORD=secret');

    $this->actingAs($this->user);

    $response = $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'env',
        'variables' => [
            '*' => ['key', 'value', 'is_secret'],
        ],
    ]);
});

test('only secret keys are stored in db', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'APP_NAME', 'value' => 'TestApp', 'is_secret' => false],
            ['key' => 'DB_PASSWORD', 'value' => 'supersecret123', 'is_secret' => true],
        ],
    ])->assertSessionDoesntHaveErrors();

    $this->site->refresh();

    expect($this->site->env_variables)->toEqual(['DB_PASSWORD']);
});

test('secret values are masked for read only members', function () {
    SSH::fake('APP_NAME=TestApp'.PHP_EOL.'DB_PASSWORD=supersecret123');

    vitoPestFeatureApplicationTestMakeUserReadOnly();

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $response = $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]));

    $response->assertOk();
    $response->assertJsonMissingPath('env');
    expect($response->json('can_edit'))->toBeFalse();

    $data = $response->json('variables');

    $secretVar = collect($data)->firstWhere('key', 'DB_PASSWORD');
    expect($secretVar['is_secret'])->toBeTrue();
    expect($secretVar['value'])->toEqual('');

    $normalVar = collect($data)->firstWhere('key', 'APP_NAME');
    expect($normalVar['is_secret'])->toBeFalse();
    expect($normalVar['value'])->toEqual('TestApp');
});

test('secret values are revealed for writers', function () {
    $env = 'APP_NAME=TestApp'.PHP_EOL.'DB_PASSWORD=supersecret123';

    SSH::fake($env);

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $response = $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]));

    $response->assertOk();
    expect($response->json('can_edit'))->toBeTrue();
    expect($response->json('env'))->toEqual($env);

    $secretVar = collect($response->json('variables'))->firstWhere('key', 'DB_PASSWORD');
    expect($secretVar['is_secret'])->toBeTrue();
    expect($secretVar['value'])->toEqual('supersecret123');
});

test('secret classification survives legacy db shape', function () {
    SSH::fake('APP_NAME=TestApp'.PHP_EOL.'DB_PASSWORD=supersecret123');

    vitoPestFeatureApplicationTestMakeUserReadOnly();

    $this->actingAs($this->user);

    $this->site->update([
        'env_variables' => [
            ['key' => 'APP_NAME', 'value' => 'TestApp', 'is_secret' => false],
            ['key' => 'DB_PASSWORD', 'value' => 'supersecret123', 'is_secret' => true],
        ],
    ]);

    $response = $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]));

    $response->assertOk();
    $secretVar = collect($response->json('variables'))->firstWhere('key', 'DB_PASSWORD');
    expect($secretVar['is_secret'])->toBeTrue();
    expect($secretVar['value'])->toEqual('');
});

test('secret value preserved on server when submitted empty', function () {
    $ssh = SSH::fake('DB_PASSWORD=original_secret');

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'DB_PASSWORD', 'value' => '', 'is_secret' => true],
        ],
    ])->assertSessionDoesntHaveErrors();

    $this->assertStringContainsString('DB_PASSWORD=original_secret', $ssh->getUploadedContent());
});

test('secret value reflects live server file when changed out of band', function () {
    $ssh = SSH::fake('APP_NAME=TestApp'.PHP_EOL.'DB_PASSWORD=rotated_secret');

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'APP_NAME', 'value' => 'ChangedApp', 'is_secret' => false],
            ['key' => 'DB_PASSWORD', 'value' => '', 'is_secret' => true],
        ],
    ])->assertSessionDoesntHaveErrors();

    $uploaded = $ssh->getUploadedContent();
    $this->assertStringContainsString('DB_PASSWORD=rotated_secret', $uploaded);
    $this->assertStringContainsString('APP_NAME=ChangedApp', $uploaded);
});

test('stored secret cannot be wiped by marking it non secret', function () {
    $ssh = SSH::fake('DB_PASSWORD=original_secret');

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'DB_PASSWORD', 'value' => '', 'is_secret' => false],
        ],
    ])->assertSessionDoesntHaveErrors();

    $this->assertStringContainsString('DB_PASSWORD=original_secret', $ssh->getUploadedContent());

    $this->site->refresh();
    expect($this->site->env_variables)->toEqual(['DB_PASSWORD']);
});

test('secret can be dropped to non secret with new value', function () {
    $ssh = SSH::fake('DB_PASSWORD=original_secret');

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'DB_PASSWORD', 'value' => 'now_plain', 'is_secret' => false],
        ],
    ])->assertSessionDoesntHaveErrors();

    $this->assertStringContainsString('DB_PASSWORD=now_plain', $ssh->getUploadedContent());

    $this->site->refresh();
    expect($this->site->env_variables)->toEqual([]);
});

test('pattern secrets masked for site never saved through vito', function () {
    SSH::fake('APP_NAME=TestApp'.PHP_EOL.'APP_KEY=base64:supersecret');

    vitoPestFeatureApplicationTestMakeUserReadOnly();

    $this->actingAs($this->user);

    expect($this->site->env_variables)->toBeNull();

    $response = $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]));

    $response->assertOk();
    $secretVar = collect($response->json('variables'))->firstWhere('key', 'APP_KEY');
    expect($secretVar['is_secret'])->toBeTrue();
    expect($secretVar['value'])->toEqual('');
});

test('raw env path writes content verbatim without restoring secrets', function () {
    $ssh = SSH::fake('DB_PASSWORD=original_secret');

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $raw = '# leading comment'.PHP_EOL.'APP_NAME=Raw'.PHP_EOL.PHP_EOL.'DB_PASSWORD=';

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => $raw,
    ])->assertSessionDoesntHaveErrors();

    $uploaded = $ssh->getUploadedContent();
    expect($uploaded)->toBe($raw);
    $this->assertStringNotContainsString('DB_PASSWORD=original_secret', $uploaded);
});

test('update env aborts when live file cannot be read', function () {
    SSH::fake('');

    $this->actingAs($this->user);

    $this->site->update(['env_variables' => ['DB_PASSWORD']]);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'DB_PASSWORD', 'value' => '', 'is_secret' => true],
        ],
    ])->assertSessionHasErrors('variables');
});

test('parse env endpoint', function () {
    $this->actingAs($this->user);

    $envContent = "APP_NAME=TestApp\nDB_PASSWORD=secret123\nAPP_DEBUG=true";

    $response = $this->post(route('application.parse-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'content' => $envContent,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'variables' => [
            '*' => ['key', 'value', 'is_secret'],
        ],
    ]);

    $variables = $response->json('variables');
    expect($variables)->toHaveCount(3);

    // Check that DB_PASSWORD is detected as secret
    $passwordVar = collect($variables)->firstWhere('key', 'DB_PASSWORD');
    expect($passwordVar['is_secret'])->toBeTrue();

    // Check that APP_NAME is not secret
    $nameVar = collect($variables)->firstWhere('key', 'APP_NAME');
    expect($nameVar['is_secret'])->toBeFalse();
});

test('git hook deployment', function (string $provider, array $webhook, string $url, array $payload, bool $skip) {
    SSH::fake();
    Http::fake([
        $url => Http::response($payload),
    ]);

    $this->site->update([
        'branch' => 'main',
    ]);
    $this->site->sourceControl->update([
        'provider' => $provider,
    ]);

    GitHook::factory()->create([
        'site_id' => $this->site->id,
        'source_control_id' => $this->site->source_control_id,
        'secret' => 'secret',
        'events' => ['push'],
        'actions' => ['deploy'],
    ]);

    $this->site->deploymentScript->update([
        'content' => 'git pull',
    ]);

    $this->post(route('api.git-hooks', [
        'secret' => 'secret',
    ]), $webhook)->assertSessionDoesntHaveErrors();

    if ($skip) {
        $this->assertDatabaseMissing('deployments', [
            'site_id' => $this->site->id,
            'deployment_script_id' => $this->site->deploymentScript->id,
            'status' => DeploymentStatus::FINISHED,
        ]);

        return;
    }

    $this->assertDatabaseHas('deployments', [
        'site_id' => $this->site->id,
        'deployment_script_id' => $this->site->deploymentScript->id,
        'status' => DeploymentStatus::FINISHED,
    ]);

    $deployment = $this->site->deployments()->first();
    expect($deployment->commit_data['name'])->toEqual('saeed');
    expect($deployment->commit_data['email'])->toEqual('saeed@vitodeploy.com');
})->with('hookData');

test('git hook deployment invalid secret', function () {
    SSH::fake();
    Http::fake();

    GitHook::factory()->create([
        'site_id' => $this->site->id,
        'source_control_id' => $this->site->source_control_id,
        'secret' => 'secret',
        'events' => ['push'],
        'actions' => ['deploy'],
    ]);

    $this->site->deploymentScript->update([
        'content' => 'git pull',
    ]);

    $this->post(route('api.git-hooks'), [
        'secret' => 'invalid-secret',
    ])->assertNotFound();

    $this->assertDatabaseMissing('deployments', [
        'site_id' => $this->site->id,
        'deployment_script_id' => $this->site->deploymentScript->id,
        'status' => DeploymentStatus::FINISHED,
    ]);
});

dataset('hookData', function () {
    return [
        [
            'github',
            [
                'ref' => 'refs/heads/main',
            ],
            'github.com/*',
            [
                'sha' => '123',
                'commit' => [
                    'committer' => [
                        'name' => 'saeed',
                        'email' => 'saeed@vitodeploy.com',
                    ],
                    'message' => 'test commit message',
                    'url' => 'https://github.com',
                ],
            ],
            false,
        ],
        [
            'github',
            [
                'ref' => 'refs/heads/other-branch',
            ],
            'github.com/*',
            [
                'sha' => '123',
                'commit' => [
                    'committer' => [
                        'name' => 'saeed',
                        'email' => 'saeed@vitodeploy.com',
                    ],
                    'message' => 'test commit message',
                    'url' => 'https://github.com',
                ],
            ],
            true,
        ],
        [
            'gitlab',
            [
                'ref' => 'main',
            ],
            'gitlab.com/*',
            [
                [
                    'id' => '123',
                    'committer_name' => 'saeed',
                    'committer_email' => 'saeed@vitodeploy.com',
                    'title' => 'test',
                    'web_url' => 'https://gitlab.com',
                ],
            ],
            false,
        ],
        [
            'gitlab',
            [
                'ref' => 'other-branch',
            ],
            'gitlab.com/*',
            [
                [
                    'id' => '123',
                    'committer_name' => 'saeed',
                    'committer_email' => 'saeed@vitodeploy.com',
                    'title' => 'test',
                    'web_url' => 'https://gitlab.com',
                ],
            ],
            true,
        ],
        [
            'bitbucket',
            [
                'push' => [
                    'changes' => [
                        [
                            'new' => [
                                'name' => 'main',
                            ],
                        ],
                    ],
                ],
            ],
            'bitbucket.org/*',
            [
                'values' => [
                    [
                        'hash' => '123',
                        'author' => [
                            'raw' => 'saeed <saeed@vitodeploy.com>',
                        ],
                        'message' => 'test',
                        'links' => [
                            'html' => [
                                'href' => 'https://bitbucket.org',
                            ],
                        ],
                    ],
                ],
            ],
            false,
        ],
        [
            'bitbucket',
            [
                'push' => [
                    'changes' => [
                        [
                            'new' => [
                                'name' => 'other-branch',
                            ],
                        ],
                    ],
                ],
            ],
            'bitbucket.org/*',
            [
                'values' => [
                    [
                        'hash' => '123',
                        'author' => [
                            'raw' => 'saeed <saeed@vitodeploy.com>',
                        ],
                        'message' => 'test',
                        'links' => [
                            'html' => [
                                'href' => 'https://bitbucket.org',
                            ],
                        ],
                    ],
                ],
            ],
            true,
        ],
    ];
});

test('deploy classic restarts only site workers', function () {
    $sshFake = SSH::fake('fake output');
    Http::fake([
        'github.com/*' => Http::response([
            'sha' => '123',
            'commit' => [
                'message' => 'test commit message',
                'name' => 'test commit name',
                'email' => 'test@example.com',
                'url' => 'https://github.com/commit-url',
            ],
        ]),
    ]);
    Notification::fake();

    // Create a worker for the site being deployed
    $siteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    // Create another site with workers on the same server
    $otherSite = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);
    $otherSiteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $otherSite->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    // Enable restart workers for the deployment script
    $this->site->deploymentScript->update([
        'content' => 'git pull',
        'configs' => ['restart_workers' => true],
    ]);

    $this->actingAs($this->user);

    $this->post(route('application.deploy', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSessionDoesntHaveErrors();

    // Verify that only the site worker restart command was executed
    SSH::assertExecutedContains('supervisorctl restart '.$siteWorker->id.':*');

    // Verify that other site's worker and "restart all" are not executed
    vitoPestFeatureApplicationTestAssertWorkerNotRestarted($otherSiteWorker->id);
    SSH::assertNotExecutedContains('supervisorctl restart all', 'Should not restart all workers');
});

test('deploy modern restarts only site workers', function () {
    $sshFake = SSH::fake('fake output');
    Http::fake([
        'github.com/*' => Http::response([
            'sha' => '123',
            'commit' => [
                'message' => 'test commit message',
                'name' => 'test commit name',
                'email' => 'test@example.com',
                'url' => 'https://github.com/commit-url',
            ],
        ]),
    ]);
    Notification::fake();

    $this->site->update([
        'type_data' => [
            'modern_deployment' => true,
            'modern_deployment_history' => 10,
            'modern_deployment_shared_resources' => ['.env'],
        ],
    ]);
    $this->site->ensureDeploymentScriptsExist();
    $this->site->refresh();

    // Create a worker for the site being deployed
    $siteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    // Create another site with workers on the same server
    $otherSite = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);
    $otherSiteWorker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $otherSite->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    // Enable restart workers for the pre-flight script
    $this->site->preFlightScript->update([
        'content' => 'php artisan migrate --force',
        'configs' => ['restart_workers' => true],
    ]);

    $this->actingAs($this->user);

    $this->post(route('application.deploy', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSessionDoesntHaveErrors();

    // Verify that only the site worker restart command was executed
    SSH::assertExecutedContains('supervisorctl restart '.$siteWorker->id.':*');

    // Verify that other site's worker and "restart all" are not executed
    vitoPestFeatureApplicationTestAssertWorkerNotRestarted($otherSiteWorker->id);
    SSH::assertNotExecutedContains('supervisorctl restart all', 'Should not restart all workers');
});

/**
 * Assert that the given worker's restart command was not executed via SSH.
 */
function vitoPestFeatureApplicationTestAssertWorkerNotRestarted(int|string $workerId): void
{
    SSH::assertNotExecutedContains(
        'supervisorctl restart '.$workerId.':*',
        "Worker {$workerId} should not be restarted"
    );
}

test('read only member cannot override env path', function () {
    SSH::fake('APP_NAME=TestApp');

    vitoPestFeatureApplicationTestMakeUserReadOnly();

    $this->actingAs($this->user);

    $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
        'env' => $this->site->path.'/other/.env',
    ]))->assertForbidden();
});

test('read only member can read the default env path', function () {
    SSH::fake('APP_NAME=TestApp');

    vitoPestFeatureApplicationTestMakeUserReadOnly();

    $this->actingAs($this->user);

    $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
        'env' => $this->site->path.'/.env',
    ]))->assertOk();
});

dataset('rejectedEnvPathProvider', function () {
    return [
        'absolute path outside the site' => ['/etc/passwd'],
        'shell metacharacters' => ['/etc/passwd;id'],
        'export prefix' => ['export /etc/passwd'],
    ];
});

test('env path outside the site is rejected', function (string $path) {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->getJson(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
        'env' => $path,
    ]))->assertStatus(422);
})->with('rejectedEnvPathProvider');

test('env path traversal is rejected', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->getJson(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
        'env' => $this->site->path.'/../other/.env',
    ]))->assertStatus(422);
});

test('array env param is treated as no override', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]).'?env[]=/etc/passwd')->assertOk();
});

test('stored env path outside the site is still readable', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->site->jsonUpdate('type_data', 'env_path', '/home/vito/other-site/.env');

    $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
        'env' => '/home/vito/other-site/.env',
    ]))->assertOk();
});

test('env path within the site is accepted', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
        'env' => $this->site->path.'/nested/.env',
    ]))->assertOk();
});

test('reading env does not persist the overridden path', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
        'env' => $this->site->path.'/nested/.env',
    ]))->assertOk();

    $this->site->refresh();

    expect(data_get($this->site->type_data, 'env_path'))->toBeNull();
});

test('missing env file still returns ok', function () {
    SSH::fake('');

    $this->actingAs($this->user);

    $response = $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]));

    $response->assertOk();
    expect($response->json('variables'))->toEqual([]);
});

test('ssh failure while reading env surfaces as an error', function () {
    $ssh = SSH::fake();
    $ssh->execWillFail();

    $this->actingAs($this->user);

    $this->get(route('application.env', [
        'server' => $this->server,
        'site' => $this->site,
    ]))->assertStatus(500);
});

test('update env rejects an empty path', function () {
    SSH::fake();

    $this->expectException(ValidationException::class);

    app(UpdateEnv::class)->update($this->site, ['env' => 'APP_NAME=Test', 'path' => '']);
});

test('update env rejects an array path', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_NAME=Test',
        'path' => ['x'],
    ])->assertSessionHasErrors('path');
});

test('update env rejects an empty variables array', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [],
    ])->assertSessionHasErrors('variables');
});

test('update env rejects an empty variables array alongside env', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => '',
        'variables' => [],
    ])->assertSessionHasErrors('variables');
});

test('update env rejects a null env sent alongside variables', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->putJson(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => null,
        'variables' => null,
    ])->assertStatus(422);
});

test('update env rejects a submission with both keys', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_NAME=Test',
        'variables' => [
            ['key' => 'APP_NAME', 'value' => 'Test', 'is_secret' => false],
        ],
    ])->assertSessionHasErrors('env');
});

test('update env rejects a submission with neither key', function () {
    SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'path' => $this->site->path.'/.env',
    ])->assertSessionHasErrors('env');
});

test('classic mode can blank the env file', function () {
    $ssh = SSH::fake('APP_NAME=TestApp');

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => '',
    ])->assertSessionDoesntHaveErrors();

    expect(trim($ssh->getUploadedContent() ?? 'not-empty'))->toEqual('');
});

test('read only member cannot update env', function () {
    SSH::fake();

    vitoPestFeatureApplicationTestMakeUserReadOnly();

    $this->actingAs($this->user);

    $this->put(route('application.update-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'env' => 'APP_NAME=Test',
    ])->assertForbidden();
});

test('parse env accepts empty content', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('application.parse-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), ['content' => '']);

    $response->assertOk();
    expect($response->json('variables'))->toEqual([]);
});

test('parse env marks a commented file as representable', function () {
    $this->actingAs($this->user);

    $content = '# Application'.PHP_EOL.PHP_EOL.'APP_NAME=TestApp'.PHP_EOL.'MY-KEY=1'.PHP_EOL.'2FA_ENABLED=1'.PHP_EOL."A='single quoted'";

    $this->post(route('application.parse-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), ['content' => $content])->assertOk()->assertJsonPath('representable', true);
});

test('stringify env serialises rows', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('application.stringify-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'variables' => [
            ['key' => 'APP_NAME', 'value' => 'TestApp'],
            ['key' => 'APP_DEBUG', 'value' => ''],
        ],
    ]);

    $response->assertOk();
    expect($response->json('env'))->toEqual('APP_NAME=TestApp'.PHP_EOL.'APP_DEBUG=');
});

test('stringify env requires variables', function () {
    $this->actingAs($this->user);

    $this->post(route('application.stringify-env', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [])->assertSessionHasErrors('variables');
});

/**
 * Demote the acting user to the read-only project role.
 */
function vitoPestFeatureApplicationTestMakeUserReadOnly(): void
{
    test()->server->project->users()->where('user_id', test()->user->id)->update([
        'role' => UserRole::USER,
    ]);
}
