<?php

namespace App\Services\LogAnalysis\GoAccess;

use App\Actions\CronJob\DeleteCronJob;
use App\Actions\SiteStats\SyncGoAccessServer;
use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Services\AbstractService;
use Closure;
use Illuminate\Validation\Rule;

class GoAccess extends AbstractService
{
    public const BASE_DIR = '/var/lib/goaccess';

    public const CRON_COMMAND = 'bash /var/lib/goaccess/bin/run.sh';

    public const CRON_FREQUENCY = '0 * * * *';

    public const SCRIPT_VERSION = 1;

    public const CONF_VERSION = 1;

    public static function id(): string
    {
        return 'goaccess';
    }

    public static function type(): string
    {
        return 'log_analysis';
    }

    public function unit(): string
    {
        return '';
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->service->server->service('log_analysis')) {
                        $fail('You already have a log analysis service on the server.');
                    }
                },
            ],
            'version' => [
                'required',
                Rule::in(['latest']),
            ],
        ];
    }

    public function creationData(array $input): array
    {
        return [
            'data_retention' => 12,
        ];
    }

    public function data(): array
    {
        return [
            'data_retention' => $this->service->type_data['data_retention'] ?? 12,
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.log_analysis.goaccess.install', [
                    'user' => $this->service->server->getSshUser(),
                ]),
                'install-goaccess'
            );

        app(SyncGoAccessServer::class)->sync($this->service->server);

        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $cron = $this->service->server->cronJobs()
            ->where('user', 'root')
            ->where('hidden', true)
            ->where('command', self::CRON_COMMAND)
            ->first();
        if ($cron) {
            app(DeleteCronJob::class)->delete($this->service->server, $cron);
        }

        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.log_analysis.goaccess.uninstall'),
                'uninstall-goaccess'
            );

        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            "goaccess --version | grep -oE '[0-9]+\.[0-9]+(\.[0-9]+)?' | head -n1"
        );

        return trim($version);
    }

    public function canBeManaged(): bool
    {
        return true;
    }

    /**
     * @throws SSHError
     */
    public function manage(string $action): bool
    {
        $enabled = ! in_array($action, ['stop', 'disable'], true);

        return $this->toggleCron($enabled);
    }

    /**
     * @throws SSHError
     */
    private function toggleCron(bool $enabled): bool
    {
        $server = $this->service->server;

        $cron = $server->cronJobs()
            ->where('user', 'root')
            ->where('hidden', true)
            ->where('command', self::CRON_COMMAND)
            ->first();

        if (! $cron) {
            if ($enabled) {
                app(SyncGoAccessServer::class)->sync($server);
            }

            return true;
        }

        $cron->status = $enabled ? CronjobStatus::READY : CronjobStatus::DISABLED;
        $cron->save();

        $server->cron()->update('root', CronJob::crontab($server, 'root'));

        return true;
    }
}
