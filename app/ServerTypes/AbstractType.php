<?php

namespace App\ServerTypes;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\SSH\Services\PHP\PHP;

abstract class AbstractType implements ServerType
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

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

    protected function progress(int $percentage, ?string $step = null): void
    {
        $this->server->progress = $percentage;
        $this->server->progress_step = $step;
        $this->server->save();
    }

    protected function addWebserver(string $service): void
    {
        if ($service != 'none') {
            $this->server->services()->create([
                'type' => 'webserver',
                'name' => $service,
                'version' => 'latest',
            ]);
        }
    }

    protected function addDatabase(string $service): void
    {
        if ($service != 'none') {
            $this->server->services()->create([
                'type' => 'database',
                'name' => config('core.databases_name.'.$service),
                'version' => config('core.databases_version.'.$service),
            ]);
        }
    }

    protected function addPHP(string $version): void
    {
        if ($version != 'none') {
            $this->server->services()->create([
                'type' => 'php',
                'type_data' => [
                    'extensions' => [],
                ],
                'name' => 'php',
                'version' => $version,
            ]);
        }
    }

    protected function addSupervisor(): void
    {
        $this->server->services()->create([
            'type' => 'process_manager',
            'name' => 'supervisor',
            'version' => 'latest',
        ]);
    }

    protected function addRedis(): void
    {
        $this->server->services()->create([
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
        ]);
    }

    protected function addUfw(): void
    {
        $this->server->services()->create([
            'type' => 'firewall',
            'name' => 'ufw',
            'version' => 'latest',
        ]);
    }
}
