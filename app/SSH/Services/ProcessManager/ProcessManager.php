<?php

namespace App\SSH\Services\ProcessManager;

use App\SSH\Services\ServiceInterface;

interface ProcessManager extends ServiceInterface
{
    public function create(
        int $id,
        string $command,
        string $user,
        bool $autoStart,
        bool $autoRestart,
        int $numprocs,
        string $logFile,
        ?int $siteId = null
    ): void;

    public function delete(int $id, ?int $siteId = null): void;

    public function restart(int $id, ?int $siteId = null): void;

    public function stop(int $id, ?int $siteId = null): void;

    public function start(int $id, ?int $siteId = null): void;

    public function getLogs(string $user, string $logPath): string;
}
