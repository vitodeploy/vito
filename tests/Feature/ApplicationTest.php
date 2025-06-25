<?php

namespace Tests\Feature;

use App\Enums\DeploymentStatus;
use App\Facades\SSH;
use App\Models\GitHook;
use App\Notifications\DeploymentCompleted;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_update_deployment_script(): void
    {
        $this->actingAs($this->user);

        $this->put(route('application.update-deployment-script', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'script' => 'some script',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('deployment_scripts', [
            'site_id' => $this->site->id,
            'content' => 'some script',
        ]);
    }

    /**
     * @throws Exception
     */
    public function test_deploy(): void
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

    public function test_update_env_file(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->put(route('application.update-env', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'env' => 'APP_ENV="production"',
            'path' => '/home/vito/some-path/.env',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertEquals('/home/vito/some-path/.env', data_get($this->site->type_data, 'env_path'));
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
}
