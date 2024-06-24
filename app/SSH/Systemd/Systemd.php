<?php

namespace App\SSH\Systemd;

use App\Models\Server;

class Systemd
{
    public function __construct(protected Server $server) {}

    public function status(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('status-%s', $unit));
    }

    public function start(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl start $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('start-%s', $unit));
    }

    public function stop(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl stop $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('stop-%s', $unit));
    }

    public function restart(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl restart $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('restart-%s', $unit));
    }

    public function enable(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl start $unit
            sudo systemctl enable $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('enable-%s', $unit));
    }

    public function disable(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl stop $unit
            sudo systemctl disable $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('disable-%s', $unit));
    }
}
