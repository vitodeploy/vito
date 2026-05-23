<?php

namespace App\SSH\Mise;

use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\Site;

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
    public function installRuntime(Site $site, string $runtime, string $version): void
    {
        $this->server->ssh($site->user)->exec(
            view('ssh.mise.install-runtime', [
                'runtime' => $runtime,
                'version' => $version,
            ]),
            'mise-install-'.$runtime.'-'.$version,
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function uninstallRuntime(Site $site, string $runtime): void
    {
        $this->server->ssh($site->user)->exec(
            view('ssh.mise.uninstall-runtime', [
                'runtime' => $runtime,
            ]),
            'mise-uninstall-'.$runtime,
            $site->id
        );
    }
}
