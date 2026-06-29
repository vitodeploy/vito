<?php

namespace Tests\Feature;

use App\Enums\DeploymentStatus;
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
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_application(): void
    {
        $this->actingAs($this->user);

        $this->get(route('application', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('application/index'));
    }

    public function test_application_page_passes_null_worker_for_non_proxied_site(): void
    {
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
    }

    public function test_application_page_passes_bootstrap_worker_for_proxied_site(): void
    {
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
    }

    public function test_update_deployment_script(): void
    {
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
        $this->assertTrue($deploymentScript->shouldRestartWorkers());
    }

    /**
     * @throws Exception
     */
    public function test_deploy_classic(): void
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
    }

    public function test_deploy_broadcasts_deployment_created(): void
    {
        $this->assertBroadcastsDeploymentCreated(function (): void {
            $this->site->deploymentScript->update([
                'content' => 'git pull',
            ]);
        });
    }

    public function test_deploy_modern_broadcasts_deployment_created(): void
    {
        $this->assertBroadcastsDeploymentCreated(function (): void {
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
    }

    private function assertBroadcastsDeploymentCreated(callable $siteSetup): void
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

        $this->actingAs($this->user);

        $this->post(route('application.deploy', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSessionDoesntHaveErrors();

        Event::assertDispatched(
            SocketEvent::class,
            fn (SocketEvent $event) => $event->data->type === 'deployment.created'
                && $event->data->data['site_id'] === $this->site->id
                && $event->data->projectId === $this->server->project_id,
        );
    }

    public function test_deploy_reverse_proxy_without_port_and_start_command_shows_error(): void
    {
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
    }

    public function test_deploy_reverse_proxy_with_port_and_start_command_is_allowed(): void
    {
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
    }

    public function test_deploy_modern(): void
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

        $this->assertNotNull($lastDeployment->release);

        SSH::assertExecutedContains('composer install');

        Notification::assertSentTo($this->notificationChannel, DeploymentCompleted::class);
    }

    public function test_rollback(): void
    {
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
    }

    public function test_enable_auto_deployment(): void
    {
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

        $this->assertTrue($this->site->isAutoDeployment());
    }

    public function test_delete_release(): void
    {
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
    }

    public function test_disable_auto_deployment(): void
    {
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

        $this->assertFalse($this->site->isAutoDeployment());
    }

    public function test_disable_auto_deployment_even_if_hook_destroy_fails(): void
    {
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

        $this->assertFalse($this->site->isAutoDeployment());
    }

    public function test_update_env_file(): void
    {
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

        $this->assertEquals($this->site->path.'/.env', data_get($this->site->type_data, 'env_path'));
    }

    public function test_update_env_file_with_path(): void
    {
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

        $this->assertEquals($this->site->path.'/some-path/.env', data_get($this->site->type_data, 'env_path'));
    }

    public function test_update_env_blocks_path_outside_site_directory(): void
    {
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
    }

    public function test_update_env_allows_stored_env_path_outside_site_directory(): void
    {
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
    }

    public function test_update_env_blocks_path_traversal(): void
    {
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
    }

    public function test_update_env_file_with_variables(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->put(route('application.update-env', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'variables' => [
                ['key' => 'APP_ENV', 'value' => 'production'],
                ['key' => 'APP_DEBUG', 'value' => 'false'],
                ['key' => 'DB_PASSWORD', 'value' => 'secret123'],
            ],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertEquals($this->site->path.'/.env', data_get($this->site->type_data, 'env_path'));
    }

    public function test_get_env_returns_variables(): void
    {
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
    }

    public function test_only_secret_keys_are_stored_in_db(): void
    {
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

        $this->assertEquals(['DB_PASSWORD'], $this->site->env_variables);
    }

    public function test_secret_values_are_masked_on_read(): void
    {
        SSH::fake('APP_NAME=TestApp'.PHP_EOL.'DB_PASSWORD=supersecret123');

        $this->actingAs($this->user);

        $this->site->update(['env_variables' => ['DB_PASSWORD']]);

        $response = $this->get(route('application.env', [
            'server' => $this->server,
            'site' => $this->site,
        ]));

        $response->assertOk();
        $data = $response->json('variables');

        $secretVar = collect($data)->firstWhere('key', 'DB_PASSWORD');
        $this->assertTrue($secretVar['is_secret']);
        $this->assertEquals('', $secretVar['value']);

        $normalVar = collect($data)->firstWhere('key', 'APP_NAME');
        $this->assertFalse($normalVar['is_secret']);
        $this->assertEquals('TestApp', $normalVar['value']);
    }

    public function test_secret_classification_survives_legacy_db_shape(): void
    {
        SSH::fake('APP_NAME=TestApp'.PHP_EOL.'DB_PASSWORD=supersecret123');

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
        $this->assertTrue($secretVar['is_secret']);
        $this->assertEquals('', $secretVar['value']);
    }

    public function test_secret_value_preserved_on_server_when_submitted_empty(): void
    {
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
    }

    public function test_secret_value_reflects_live_server_file_when_changed_out_of_band(): void
    {
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
    }

    public function test_stored_secret_cannot_be_wiped_by_marking_it_non_secret(): void
    {
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
        $this->assertEquals(['DB_PASSWORD'], $this->site->env_variables);
    }

    public function test_secret_can_be_dropped_to_non_secret_with_new_value(): void
    {
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
        $this->assertEquals([], $this->site->env_variables);
    }

    public function test_pattern_secrets_masked_for_site_never_saved_through_vito(): void
    {
        SSH::fake('APP_NAME=TestApp'.PHP_EOL.'APP_KEY=base64:supersecret');

        $this->actingAs($this->user);

        $this->assertNull($this->site->env_variables);

        $response = $this->get(route('application.env', [
            'server' => $this->server,
            'site' => $this->site,
        ]));

        $response->assertOk();
        $secretVar = collect($response->json('variables'))->firstWhere('key', 'APP_KEY');
        $this->assertTrue($secretVar['is_secret']);
        $this->assertEquals('', $secretVar['value']);
    }

    public function test_raw_env_path_writes_content_verbatim_without_restoring_secrets(): void
    {
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
        $this->assertSame($raw, $uploaded);
        $this->assertStringNotContainsString('DB_PASSWORD=original_secret', $uploaded);
    }

    public function test_update_env_aborts_when_live_file_cannot_be_read(): void
    {
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
    }

    public function test_parse_env_endpoint(): void
    {
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
        $this->assertCount(3, $variables);

        // Check that DB_PASSWORD is detected as secret
        $passwordVar = collect($variables)->firstWhere('key', 'DB_PASSWORD');
        $this->assertTrue($passwordVar['is_secret']);

        // Check that APP_NAME is not secret
        $nameVar = collect($variables)->firstWhere('key', 'APP_NAME');
        $this->assertFalse($nameVar['is_secret']);
    }

    /**
     * @param  array<string, mixed>  $webhook
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('hookData')]
    public function test_git_hook_deployment(string $provider, array $webhook, string $url, array $payload, bool $skip): void
    {
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
        $this->assertEquals('saeed', $deployment->commit_data['name']);
        $this->assertEquals('saeed@vitodeploy.com', $deployment->commit_data['email']);
    }

    public function test_git_hook_deployment_invalid_secret(): void
    {
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
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function hookData(): array
    {
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
    }

    public function test_deploy_classic_restarts_only_site_workers(): void
    {
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
        $this->assertWorkerNotRestarted($otherSiteWorker->id);
        SSH::assertNotExecutedContains('supervisorctl restart all', 'Should not restart all workers');
    }

    public function test_deploy_modern_restarts_only_site_workers(): void
    {
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
        $this->assertWorkerNotRestarted($otherSiteWorker->id);
        SSH::assertNotExecutedContains('supervisorctl restart all', 'Should not restart all workers');
    }

    /**
     * Assert that the given worker's restart command was not executed via SSH.
     */
    private function assertWorkerNotRestarted(int|string $workerId): void
    {
        SSH::assertNotExecutedContains(
            'supervisorctl restart '.$workerId.':*',
            "Worker {$workerId} should not be restarted"
        );
    }
}
