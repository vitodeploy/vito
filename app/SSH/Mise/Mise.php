<?php

namespace App\SSH\Mise;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;
use App\Models\Server;

class Mise
{
    public function __construct(protected Server $server) {}

    /**
     * @throws SSHError
     */
    public function ensureInstalled(): void
    {
        $this->server->ssh()->exec(
            view('ssh.mise.ensure-installed'),
            'ensure-mise-installed'
        );
    }

    /**
     * @throws SSHError
     */
    public function isInstalled(): bool
    {
        $result = $this->server->ssh()->exec('which mise || echo "not-found"');

        return ! str_contains($result, 'not-found');
    }

    /**
     * @throws SSHError
     */
    public function installRuntime(ToolingTargetDTO $target, string $runtime, string $version): void
    {
        $this->server->ssh($target->user)->exec(
            view('ssh.mise.install-runtime', [
                'runtime' => $runtime,
                'version' => $version,
            ]),
            'mise-install-'.$runtime.'-'.$version,
            $target->siteId
        );
    }

    /**
     * @throws SSHError
     */
    public function uninstallRuntime(ToolingTargetDTO $target, string $runtime): void
    {
        $this->server->ssh($target->user)->exec(
            view('ssh.mise.uninstall-runtime', [
                'runtime' => $runtime,
            ]),
            'mise-uninstall-'.$runtime,
            $target->siteId
        );
    }
}
