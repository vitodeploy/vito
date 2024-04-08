<?php

namespace App\SSH\Services\ProcessManager;

use App\SSH\HasScripts;
use Throwable;

class Supervisor extends AbstractProcessManager
{
    use HasScripts;

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('supervisor/install-supervisor.sh'),
            'install-supervisor'
        );
        $this->service->server->os()->cleanup();
    }

    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('supervisor/uninstall-supervisor.sh'),
            'uninstall-supervisor'
        );
        $status = $this->service->server->systemd()->status($this->service->unit);
        $this->service->validateInstall($status);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $id,
        string $command,
        string $user,
        bool $autoStart,
        bool $autoRestart,
        int $numprocs,
        string $logFile,
        ?int $siteId = null
    ): void {
        $this->service->server->ssh($user)->exec(
            $this->getScript('supervisor/create-worker.sh', [
                'id' => $id,
                'config' => $this->generateConfigFile(
                    $id,
                    $command,
                    $user,
                    $autoStart,
                    $autoRestart,
                    $numprocs,
                    $logFile
                ),
            ]),
            'create-worker',
            $siteId
        );
    }

    /**
     * @throws Throwable
     */
    public function delete(int $id, ?int $siteId = null): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('supervisor/delete-worker.sh', [
                'id' => $id,
            ]),
            'delete-worker',
            $siteId
        );
    }

    /**
     * @throws Throwable
     */
    public function restart(int $id, ?int $siteId = null): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('supervisor/restart-worker.sh', [
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
            $this->getScript('supervisor/stop-worker.sh', [
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
            $this->getScript('supervisor/start-worker.sh', [
                'id' => $id,
            ]),
            'start-worker',
            $siteId
        );
    }

    /**
     * @throws Throwable
     */
    public function getLogs(string $logPath): string
    {
        return $this->service->server->ssh()->exec(
            "tail -100 $logPath"
        );
    }

    private function generateConfigFile(
        int $id,
        string $command,
        string $user,
        bool $autoStart,
        bool $autoRestart,
        int $numprocs,
        string $logFile
    ): string {
        return $this->getScript('supervisor/worker.conf', [
            'name' => (string) $id,
            'command' => $command,
            'user' => $user,
            'auto_start' => var_export($autoStart, true),
            'auto_restart' => var_export($autoRestart, true),
            'numprocs' => (string) $numprocs,
            'log_file' => $logFile,
        ]);
    }
}
