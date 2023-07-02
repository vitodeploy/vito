<?php

namespace App\Jobs\PHP;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Service;
use App\SSHCommands\UpdatePHPSettingsCommand;
use Throwable;

class UpdatePHPSettings extends Job
{
    protected Service $service;

    protected array $settings;

    public function __construct(Service $service, array $settings)
    {
        $this->service = $service;
        $this->settings = $settings;
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $commands = [];
        foreach ($this->settings as $key => $value) {
            $commands[] = new UpdatePHPSettingsCommand(
                $this->service->version,
                $key,
                $value.' '.config('core.php_settings_unit')[$key]
            );
        }
        $this->service->server->ssh()->exec($commands, 'update-php-settings');
        $typeData = $this->service->type_data;
        $typeData['settings'] = $this->settings;
        $this->service->type_data = $typeData;
        $this->service->save();
        event(
            new Broadcast('update-php-settings-finished', [
                'service' => $this->service,
            ])
        );
    }

    public function failed(): void
    {
        event(
            new Broadcast('update-php-settings-failed', [
                'service' => $this->service,
            ])
        );
    }
}
