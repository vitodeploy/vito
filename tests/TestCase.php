<?php

namespace Tests;

use App\Enums\Database;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Enums\Webserver;
use App\Models\NotificationChannel;
use App\Models\Redirect;
use App\Models\Server;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected User $user;

    protected Server $server;

    protected Site $site;

    protected Redirect $redirect;

    protected NotificationChannel $notificationChannel;

    public const EXPECT_SUCCESS = true;

    public const EXPECT_FAILURE = false;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('queue.connections.ssh.driver', 'sync');
        config()->set('filesystems.disks.key-pairs.root', storage_path('app/key-pairs-test'));

        $this->user = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $this->user->createDefaultProject();

        $this->notificationChannel = NotificationChannel::factory()->create([
            'provider' => \App\Enums\NotificationChannel::EMAIL,
            'connected' => true,
            'data' => [
                'email' => 'user@example.com',
            ],
        ]);

        $this->setupServer();

        $this->setupSite();

        $this->setupKeys();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (File::exists(storage_path('app/key-pairs-test'))) {
            File::deleteDirectory(storage_path('app/key-pairs-test'));
        }
    }

    private function setupServer(): void
    {
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $keys = $this->server->sshKey();
        if (! File::exists($keys['public_key_path']) || ! File::exists($keys['private_key_path'])) {
            $this->server->provider()->generateKeyPair();
        }

        $this->server->type()->createServices([
            'webserver' => Webserver::NGINX,
            'database' => Database::MYSQL80,
            'php' => '8.2',
        ]);

        $this->server->services()->update([
            'status' => ServiceStatus::READY,
        ]);

        $this->server->database()?->update(['type_data' => [
            'charsets' => ['utf8mb3' => ['default' => 'utf8mb3_general_ci', 'list' => ['utf8mb3_general_ci']]],
            'defaultCharset' => 'utf8mb3',
        ]]);
    }

    private function setupSite(): void
    {
        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->github()->create();
        $this->site = Site::factory()->create([
            'domain' => 'vito.test',
            'aliases' => ['www.vito.test'],
            'server_id' => $this->server->id,
            'source_control_id' => $sourceControl->id,
            'repository' => 'organization/repository',
            'path' => '/home/vito/vito.test',
            'web_directory' => 'public',
            'branch' => 'main',
        ]);

        $this->redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);
    }

    private function setupKeys(): void
    {
        config()->set('core.ssh_public_key_name', 'test-key.pub');
        config()->set('core.ssh_private_key_name', 'test-key');
        $publicKeypath = storage_path(config('core.ssh_public_key_name'));
        $privateKeyPath = storage_path(config('core.ssh_private_key_name'));
        if (! File::exists($publicKeypath) || ! File::exists($privateKeyPath)) {
            generate_key_pair(storage_path('test-key'));
        }
    }
}
