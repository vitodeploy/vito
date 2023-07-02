<?php

namespace App\Jobs\PHP;

use App\Events\Broadcast;
use App\Exceptions\ProcessFailed;
use App\Jobs\Job;
use App\Models\Service;
use App\SSHCommands\InstallPHPExtensionCommand;
use Illuminate\Support\Str;
use Throwable;

class InstallPHPExtension extends Job
{
    protected Service $service;

    protected string $name;

    public function __construct(Service $service, string $name)
    {
        $this->service = $service;
        $this->name = $name;
    }

    /**
     * @throws ProcessFailed
     * @throws Throwable
     */
    public function handle(): void
    {
        $result = $this->service->server->ssh()->exec(
            new InstallPHPExtensionCommand($this->service->version, $this->name),
            'install-php-extension'
        );
        $result = Str::substr($result, strpos($result, '[PHP Modules]'));
        if (! Str::contains($result, $this->name)) {
            throw new ProcessFailed('Extension failed');
        }
        $typeData = $this->service->type_data;
        $typeData['extensions'][] = $this->name;
        $this->service->type_data = $typeData;
        $this->service->save();
        event(
            new Broadcast('install-php-extension-finished', [
                'service' => $this->service,
            ])
        );
    }

    public function failed(): void
    {
        event(
            new Broadcast('install-php-extension-failed', [
                'service' => $this->service,
            ])
        );
    }
}
