<?php

namespace App\SSH\Services\ProcessManager;

use App\SSH\HasScripts;
use App\SSH\Services\ProcessManager\AbstractProcessManager;
use App\SSHCommands\Supervisor\CreateWorkerCommand;
use App\SSHCommands\Supervisor\DeleteWorkerCommand;
use App\SSHCommands\Supervisor\RestartWorkerCommand;
use App\SSHCommands\Supervisor\StartWorkerCommand;
use App\SSHCommands\Supervisor\StopWorkerCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
            new CreateWorkerCommand(
                $id,
                $this->generateConfigFile(
                    $id,
                    $command,
                    $user,
                    $autoStart,
                    $autoRestart,
                    $numprocs,
                    $logFile
                )
            ),
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
            new DeleteWorkerCommand($id),
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
            new RestartWorkerCommand($id),
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
            new StopWorkerCommand($id),
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
            new StartWorkerCommand($id),
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
        $config = File::get(resource_path('commands/supervisor/worker.conf'));
        $config = Str::replace('__name__', (string) $id, $config);
        $config = Str::replace('__command__', $command, $config);
        $config = Str::replace('__user__', $user, $config);
        $config = Str::replace('__auto_start__', var_export($autoStart, true), $config);
        $config = Str::replace('__auto_restart__', var_export($autoRestart, true), $config);
        $config = Str::replace('__numprocs__', (string) $numprocs, $config);

        return Str::replace('__log_file__', $logFile, $config);
    }
}
