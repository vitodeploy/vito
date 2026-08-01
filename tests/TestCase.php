<?php

namespace Tests;

use App\Enums\ServiceStatus;
use App\Models\NotificationChannel;
use App\Models\Redirect;
use App\Models\Server;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use App\NotificationChannels\Email;
use App\Services\Database\Mysql;
use App\Services\Firewall\Ufw;
use App\Services\NodeJS\NodeJS;
use App\Services\PHP\PHP;
use App\Services\ProcessManager\Supervisor;
use App\Services\Redis\Redis;
use App\Services\Webserver\Nginx;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Sleep;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public User $user;

    public Server $server;

    public Site $site;

    public Redirect $redirect;

    public NotificationChannel $notificationChannel;

    public const EXPECT_SUCCESS = true;

    public const EXPECT_FAILURE = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Sleep::fake();
        config()->set('queue.connections.ssh.driver', 'sync');
        config()->set('queue.connections.default.driver', 'sync');
        config()->set('filesystems.disks.key-pairs.root', storage_path('app/key-pairs-test'.$this->parallelToken()));

        $this->user = User::factory()->create();
        $this->user->ensureHasDefaultProject();

        $this->notificationChannel = NotificationChannel::factory()->create([
            'provider' => Email::id(),
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

        if (File::exists(storage_path('app/key-pairs-test'.$this->parallelToken()))) {
            File::deleteDirectory(storage_path('app/key-pairs-test'.$this->parallelToken()));
        }
    }

    private function parallelToken(): string
    {
        $token = ParallelTesting::token();

        return $token ? '-'.$token : '';
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

        $this->server->services()->create([
            'type' => Nginx::type(),
            'name' => Nginx::id(),
            'version' => 'latest',
        ]);
        $this->server->services()->create([
            'type' => Mysql::type(),
            'name' => Mysql::id(),
            'version' => '8.4',
        ]);
        $this->server->services()->create([
            'type' => PHP::type(),
            'name' => PHP::id(),
            'version' => '8.2',
        ]);
        $this->server->services()->create([
            'type' => Ufw::type(),
            'name' => Ufw::id(),
            'version' => 'latest',
        ]);
        $this->server->services()->create([
            'type' => Supervisor::type(),
            'name' => Supervisor::id(),
            'version' => 'latest',
        ]);
        $this->server->services()->create([
            'type' => Redis::type(),
            'name' => Redis::id(),
            'version' => 'latest',
        ]);
        $this->server->services()->create([
            'type' => NodeJS::type(),
            'name' => NodeJS::id(),
            'version' => '20',
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
            'server_id' => $this->server->id,
            'source_control_id' => $sourceControl->id,
            'repository' => 'organization/repository',
            'path' => '/home/vito/vito.test',
            'web_directory' => 'public',
            'branch' => 'main',
        ]);

        $this->site->createDefaultDeploymentScript();

        $this->redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);
    }

    private function setupKeys(): void
    {
        $keyName = 'test-key'.$this->parallelToken();
        config()->set('core.ssh_public_key_name', $keyName.'.pub');
        config()->set('core.ssh_private_key_name', $keyName);
        $publicKeypath = storage_path(config('core.ssh_public_key_name'));
        $privateKeyPath = storage_path(config('core.ssh_private_key_name'));
        if (! File::exists($publicKeypath) || ! File::exists($privateKeyPath)) {
            generate_key_pair(storage_path($keyName));
        }
    }
}
