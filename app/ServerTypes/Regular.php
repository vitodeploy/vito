<?php

namespace App\ServerTypes;

use App\Events\Broadcast;
use App\Jobs\Installation\Initialize;
use App\Jobs\Installation\InstallCertbot;
use App\Jobs\Installation\InstallComposer;
use App\Jobs\Installation\InstallNodejs;
use App\Jobs\Installation\InstallRequirements;
use App\Jobs\Installation\Upgrade;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class Regular extends AbstractType
{
    public function createValidationRules(array $input): array
    {
        return [
            'webserver' => [
                'required',
                'in:'.implode(',', config('core.webservers')),
            ],
            'php' => [
                'required',
                'in:'.implode(',', config('core.php_versions')),
            ],
            'database' => [
                'required',
                'in:'.implode(',', config('core.databases')),
            ],
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function createServices(array $input): void
    {
        $this->server->services()->forceDelete();

        $this->addWebserver($input['webserver']);
        $this->addDatabase($input['database']);
        $this->addPHP($input['php']);
        $this->addSupervisor();
        $this->addRedis();
        $this->addUfw();
    }

    public function install(): void
    {
        $jobs = [
            new Initialize($this->server, $this->server->ssh_user),
            $this->progress(15, 'Installing Updates'),
            new Upgrade($this->server),
            $this->progress(25, 'Installing Requirements'),
            new InstallRequirements($this->server),
            $this->progress(35, 'Installing Node.js'),
            new InstallNodejs($this->server),
            $this->progress(45, 'Installing Certbot'),
            new InstallCertbot($this->server),
        ];

        $services = $this->server->services;
        $currentProgress = 45;
        $progressPerService = (100 - $currentProgress) / count($services);
        foreach ($services as $service) {
            $currentProgress += $progressPerService;
            $jobs[] = $this->progress($currentProgress, 'Installing '.$service->name);
            $jobs[] = $service->installer();
            if ($service->type == 'php') {
                $jobs[] = $this->progress($currentProgress, 'Installing Composer');
                $jobs[] = new InstallComposer($this->server);
            }
        }

        $jobs[] = function () {
            $this->server->update([
                'status' => 'ready',
                'progress' => 100,
            ]);
            event(
                new Broadcast('install-server-finished', [
                    'server' => $this->server,
                ])
            );
            /** @todo notify */
        };

        Bus::chain($jobs)
            ->catch(function (Throwable $e) {
                $this->server->update([
                    'status' => 'installation_failed',
                ]);
                event(
                    new Broadcast('install-server-failed', [
                        'server' => $this->server,
                    ])
                );
                /** @todo notify */
                Log::error('server-installation-error', [
                    'error' => (string) $e,
                ]);
                throw $e;
            })
            ->onConnection('ssh-long')
            ->dispatch();
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

    /**
     * add supervisor
     */
    protected function addSupervisor(): void
    {
        $this->server->services()->create([
            'type' => 'process_manager',
            'name' => 'supervisor',
            'version' => 'latest',
        ]);
    }

    /**
     * add supervisor
     */
    protected function addRedis(): void
    {
        $this->server->services()->create([
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
        ]);
    }

    /**
     * add supervisor
     */
    protected function addUfw(): void
    {
        $this->server->services()->create([
            'type' => 'firewall',
            'name' => 'ufw',
            'version' => 'latest',
        ]);
    }
}
