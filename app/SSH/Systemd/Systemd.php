<?php

namespace App\SSH\Systemd;

use App\Models\Server;

class Systemd
{
    public function __construct(protected Server $server)
    {
    }

    public function status(string $unit): string
    {
        $command = <<<EOD
            sudo service $unit status | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('status-%s', $unit));
    }

    public function start(string $unit): string
    {
        $command = <<<EOD
            sudo service $unit start
            sudo service $unit status | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('start-%s', $unit));
    }

    public function stop(string $unit): string
    {
        $command = <<<EOD
            sudo service $unit stop
            sudo service $unit status | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('stop-%s', $unit));
    }

    public function restart(string $unit): string
    {
        $command = <<<EOD
            sudo service $unit restart
            sudo service $unit status | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('restart-%s', $unit));
    }
}
