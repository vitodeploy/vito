<?php

namespace App\Services\ProcessManager;

use App\DTOs\ServiceLog;
use App\Enums\WorkerStatus;
use App\Exceptions\SSHError;
use App\Helpers\ComponentShell;
use App\Models\Worker;
use App\Services\HasLogs;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class Supervisor extends AbstractProcessManager implements HasLogs
{
    public static function id(): string
    {
        return 'supervisor';
    }

    public static function type(): string
    {
        return 'process_manager';
    }

    public function unit(): string
    {
        return 'supervisor';
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.process-manager.supervisor.install-supervisor'),
                'install-supervisor'
            );
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.uninstall-supervisor'),
            'uninstall-supervisor'
        );
        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @param  array<string, string>  $environment
     */
    public static function formatEnvironment(array $environment): string
    {
        return collect($environment)
            ->filter(fn (string $value, string $key): bool => preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) === 1)
            ->map(function (string $value, string $key): string {
                $sanitized = str_replace(["\r", "\n", '"'], '', $value);

                return $key.'="'.str_replace('%', '%%', $sanitized).'"';
            })
            ->implode(',');
    }

    /**
     * @throws SSHError
     */
    public function writeConfig(Worker $worker): void
    {
        $logFile = $worker->getLogFile();
        $previousLogFile = null;
        if (! $this->isRootSafeLogPath($logFile)) {
            $previousLogFile = $logFile;
            $logFile = $worker->defaultLogFile();
        }

        if ($logFile === $worker->defaultLogFile()) {
            $this->service->server->ssh()->exec(
                view('ssh.services.process-manager.supervisor.prepare-log', [
                    'logFile' => $logFile,
                    'previousLogFile' => $previousLogFile,
                    'user' => $worker->user,
                ])
            );
        }

        $this->service->server->ssh()->write(
            "/etc/supervisor/conf.d/{$worker->id}.conf",
            view('ssh.services.process-manager.supervisor.worker', [
                'name' => (string) $worker->id,
                'directory' => $worker->component_id === null ? $worker->site?->path : null,
                'command' => $worker->component ? new HtmlString(ComponentShell::wrap($worker->component, $worker->command)) : $worker->command,
                'user' => $worker->user,
                'autoStart' => var_export($worker->auto_start, true),
                'autoRestart' => var_export($worker->auto_restart, true),
                'numprocs' => (string) $worker->numprocs,
                'logFile' => new HtmlString($logFile),
                'environment' => $worker->component_id === null ? self::formatEnvironment($worker->effectiveEnvironment()) : '',
            ]),
            'root'
        );

        if ($worker->getOriginal('stdout_logfile') !== $logFile) {
            Worker::query()->whereKey($worker->id)->update(['stdout_logfile' => $logFile]);
            $worker->stdout_logfile = $logFile;
            $worker->syncOriginalAttribute('stdout_logfile');
        }
    }

    /**
     * @throws SSHError
     */
    public function create(Worker $worker): void
    {
        $worker->stdout_logfile = $worker->defaultLogFile();
        $this->writeConfig($worker);

        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.create-worker', [
                'id' => $worker->id,
                'start' => $worker->component_id === null || $worker->auto_start,
            ]),
            'create-worker',
            $worker->site_id
        );
    }

    /**
     * @throws Throwable
     */
    public function delete(int $id, ?int $siteId = null): void
    {
        $worker = Worker::query()->find($id);
        $logFile = $worker?->getLogFile();
        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.delete-worker', [
                'id' => $id,
                'component' => $worker?->component_id !== null,
                'logFile' => $logFile !== null && $this->isRootSafeLogPath($logFile)
                    && ($worker->status === WorkerStatus::DELETING || $logFile !== $worker->defaultLogFile()) ? $logFile : null,
            ]),
            'delete-worker',
            $siteId
        );
    }

    /**
     * Whether root can open or remove the path without following symlinks an unprivileged user planted.
     * Legacy logs inside an isolated user's home are not safe: that user owns the directory.
     */
    private function isRootSafeLogPath(string $logFile): bool
    {
        return Str::startsWith($logFile, [
            '/var/log/vito/workers/',
            '/root/',
            '/home/'.$this->service->server->getSshUser().'/',
        ]);
    }

    /**
     * @throws Throwable
     */
    public function restart(int $id, ?int $siteId = null): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.restart-worker', [
                'id' => $id,
            ]),
            'restart-worker',
            $siteId
        );
    }

    /**
     * @throws Throwable
     */
    public function stop(int $id, ?int $siteId = null): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.stop-worker', [
                'id' => $id,
            ]),
            'stop-worker',
            $siteId
        );
    }

    /**
     * @throws Throwable
     */
    public function start(int $id, ?int $siteId = null): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.start-worker', [
                'id' => $id,
            ]),
            'start-worker',
            $siteId
        );
    }

    /**
     * @param  non-empty-array<int>  $ids
     *
     * @throws Throwable
     */
    public function restartMany(array $ids, ?int $siteId = null): string
    {
        /** @phpstan-ignore identical.alwaysFalse (defensive guard despite non-empty-array contract) */
        if ($ids === []) {
            return '';
        }

        return $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.restart-workers', [
                'ids' => $ids,
            ]),
            'restart-workers',
            $siteId
        );
    }

    /**
     * @throws Throwable
     */
    public function restartAll(?int $siteId = null): void
    {
        if ($siteId !== null || $this->service->server->components()->exists()) {
            $ids = $this->service->server->workers()
                ->whereNull('component_id')
                ->when($siteId !== null, fn ($query) => $query->where('site_id', $siteId))
                ->whereNotIn('status', [WorkerStatus::CREATING, WorkerStatus::DELETING])
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                $this->restartMany($ids, $siteId);
            }

            return;
        }

        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.restart-all-workers'),
            'restart-all-workers'
        );
    }

    /**
     * @return array<int, array<string, array{state: string, description: string}>>
     *
     * @throws Throwable
     */
    public function statuses(): array
    {
        $output = $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.worker-statuses'),
            'worker-statuses'
        );

        $statuses = [];

        foreach (explode("\n", $output) as $line) {
            $parts = preg_split('/\s+/', trim($line), 3) ?: [];

            if (count($parts) < 2) {
                continue;
            }

            $group = explode(':', $parts[0], 2)[0];

            if (! ctype_digit($group)) {
                continue;
            }

            $statuses[(int) $group][$parts[0]] = [
                'state' => strtoupper($parts[1]),
                'description' => $parts[2] ?? '',
            ];
        }

        return $statuses;
    }

    /**
     * @throws Throwable
     */
    public function getLogs(string $user, string $logPath): string
    {
        return $this->service->server->ssh($this->isRootSafeLogPath($logPath) ? 'root' : $user)->exec(
            'tail -100 -- '.escapeshellarg($logPath)
        );
    }

    public function versionCommand(): ?string
    {
        return 'supervisord --version';
    }

    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'supervisor:general',
                serviceLabel: 'Supervisor',
                label: 'General log',
                source: ServiceLog::SOURCE_FILE,
                target: '/var/log/supervisor/supervisord.log',
            ),
        ];
    }
}
