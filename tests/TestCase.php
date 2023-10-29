<?php

namespace Tests;

use App\Enums\Database;
use App\Enums\ServiceStatus;
use App\Enums\Webserver;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->setupServer();

        $this->setupSite();

        $this->setupKeys();
    }

    private function setupServer(): void
    {
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->server->type()->createServices([
            'webserver' => Webserver::NGINX,
            'database' => Database::MYSQL80,
            'php' => '8.2',
        ]);

        $this->server->services()->update([
            'status' => ServiceStatus::READY,
        ]);
    }

    private function setupSite(): void
    {
        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->github()->create();
        $this->site = Site::factory()->create([
            'server_id' => $this->server->id,
            'source_control_id' => $sourceControl->id,
            'repository' => 'organization/repository',
        ]);
    }

    private function setupKeys(): void
    {
        config()->set('core.ssh_public_key_name', 'ssh-public.key');
        config()->set('core.ssh_private_key_name', 'ssh-private.pem');
        if (! File::exists(storage_path(config('core.ssh_public_key_name')))) {
            File::put(storage_path(config('core.ssh_public_key_name')), 'public_key');
        }
        if (! File::exists(storage_path(config('core.ssh_private_key_name')))) {
            File::put(storage_path(config('core.ssh_private_key_name')), 'private_key');
        }
    }
}
