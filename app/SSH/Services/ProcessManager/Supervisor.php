<?php

namespace App\SSH\Services\ProcessManager;

use App\Exceptions\SSHError;
use Throwable;

class Supervisor extends AbstractProcessManager
{
    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.install-supervisor'),
            'install-supervisor'
        );
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
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
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
        $this->service->server->ssh()->write(
            "/etc/supervisor/conf.d/$id.conf",
            view('ssh.services.process-manager.supervisor.worker', [
                'name' => (string) $id,
                'command' => $command,
                'user' => $user,
                'autoStart' => var_export($autoStart, true),
                'autoRestart' => var_export($autoRestart, true),
                'numprocs' => (string) $numprocs,
                'logFile' => $logFile,
            ]),
            true
        );

        $this->service->server->ssh()->exec(
            view('ssh.services.process-manager.supervisor.create-worker', [
                'id' => $id,
                'logFile' => $logFile,
                'user' => $user,
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
            view('ssh.services.process-manager.supervisor.delete-worker', [
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
     * @throws Throwable
     */
    public function getLogs(string $user, string $logPath): string
    {
        return $this->service->server->ssh($user)->exec(
            "tail -100 $logPath"
        );
    }
}
