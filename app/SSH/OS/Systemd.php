<?php

namespace App\SSH\OS;

use App\Exceptions\SSHError;
use App\Models\Server;
use Illuminate\Support\Facades\Log;

class Systemd
{
    public function __construct(protected Server $server) {}

    /**
     * @throws SSHError
     */
    public function status(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('status-%s', $unit));
    }

    /**
     * @param  array<int, string>  $units
     * @return array<int, string>
     *
     * @throws SSHError
     */
    public function activeStates(array $units): array
    {
        if ($units === []) {
            return [];
        }

        $command = 'sudo systemctl is-active '.implode(' ', array_map(escapeshellarg(...), $units)).' 2>/dev/null || true';

        $output = $this->server->ssh()->exec($command, timeout: 5);

        $states = array_values(array_filter(array_map(trim(...), preg_split('/\R/', $output) ?: []), fn (string $state): bool => $state !== ''));

        if (count($states) !== count($units)) {
            Log::warning('Unexpected systemctl is-active output', [
                'server_id' => $this->server->id,
                'units' => $units,
                'output' => $output,
            ]);

            return [];
        }

        return $states;
    }

    /**
     * @throws SSHError
     */
    public function start(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl start $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('start-%s', $unit));
    }

    /**
     * @throws SSHError
     */
    public function stop(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl stop $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('stop-%s', $unit));
    }

    /**
     * @throws SSHError
     */
    public function restart(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl restart $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('restart-%s', $unit));
    }

    /**
     * @throws SSHError
     */
    public function enable(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl start $unit
            sudo systemctl enable $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('enable-%s', $unit));
    }

    /**
     * @throws SSHError
     */
    public function disable(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl stop $unit
            sudo systemctl disable $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('disable-%s', $unit));
    }

    /**
     * @throws SSHError
     */
    public function reload(string $unit): string
    {
        $command = <<<EOD
            sudo systemctl reload $unit
            sudo systemctl status $unit | cat
        EOD;

        return $this->server->ssh()->exec($command, sprintf('reload-%s', $unit));
    }
}
