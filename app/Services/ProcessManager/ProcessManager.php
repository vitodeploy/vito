<?php

namespace App\Services\ProcessManager;

use App\Models\Worker;
use App\Services\ServiceInterface;

interface ProcessManager extends ServiceInterface
{
    public function create(Worker $worker): void;

    public function writeConfig(Worker $worker): void;

    public function delete(int $id, ?int $siteId = null): void;

    public function restart(int $id, ?int $siteId = null): void;

    public function stop(int $id, ?int $siteId = null): void;

    public function start(int $id, ?int $siteId = null): void;

    public function restartAll(?int $siteId = null): void;

    /**
     * @param  array<int>  $workerIds
     */
    public function restartByIds(array $workerIds, ?int $siteId = null): void;

    public function getLogs(string $user, string $logPath): string;
}
