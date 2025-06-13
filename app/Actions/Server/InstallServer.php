<?php

namespace App\Actions\Server;

use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Exceptions\SSHConnectionError;
use App\Exceptions\SSHError;
use App\Facades\Notifier;
use App\Models\Server;
use App\Notifications\ServerInstallationSucceed;
use App\Services\PHP\PHP;

class InstallServer
{
    protected Server $server;

    /**
     * @throws SSHError
     */
    public function run(Server $server): void
    {
        $this->server = $server;

        $maxWait = 180;
        while ($maxWait > 0) {
            if (! $this->server->provider()->isRunning()) {
                continue;
            }
            try {
                $this->server->ssh()->connect();
                break;
            } catch (SSHConnectionError) {
                // ignore
            }
            sleep(10);
            $maxWait -= 10;
        }
        $this->install();
        $this->server->update([
            'status' => ServerStatus::READY,
        ]);
        Notifier::send($this->server, new ServerInstallationSucceed($this->server));
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->createUser();
        $this->progress(15, 'installing-updates');
        $this->server->os()->upgrade();
        $this->progress(25, 'installing-dependencies');
        $this->server->os()->installDependencies();
        $services = $this->server->services;
        $currentProgress = 45;
        $progressPerService = (100 - $currentProgress) / count($services);
        foreach ($services as $service) {
            $currentProgress += $progressPerService;
            $this->progress($currentProgress, 'installing- '.$service->name);
            $service->handler()->install();
            $service->update(['status' => ServiceStatus::READY]);
            if ($service->type == 'php') {
                $this->progress($currentProgress, 'installing-composer');
                /** @var PHP $handler */
                $handler = $service->handler();
                $handler->installComposer();
            }
        }
        $this->progress(100, 'finishing');
    }

    /**
     * @throws SSHError
     */
    protected function createUser(): void
    {
        $this->server->os()->createUser(
            $this->server->authentication['user'],
            $this->server->authentication['pass'],
            $this->server->sshKey()['public_key']
        );
        $this->server->ssh_user = config('core.ssh_user');
        $this->server->save();
        $this->server->refresh();
        $this->server->public_key = $this->server->os()->getPublicKey($this->server->getSshUser());
        $this->server->save();
    }

    protected function progress(int|float $percentage, ?string $step = null): void
    {
        $this->server->progress = $percentage;
        $this->server->progress_step = $step;
        $this->server->save();
    }
}
