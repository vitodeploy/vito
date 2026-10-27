<?php

namespace App\SSH\Mise;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;
use App\Models\Server;
use App\Models\Site;
use InvalidArgumentException;

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
        $this->installRuntimeForTarget(ToolingTargetDTO::forSite($site), $runtime, $version);
    }

    /**
     * @throws SSHError
     */
    public function installRuntimeForTarget(ToolingTargetDTO $target, string $runtime, string $version): void
    {
        $this->assertTarget($target);

        $this->server->ssh($target->user)->exec(
            view('ssh.mise.install-runtime', [
                'runtime' => $runtime,
                'version' => $version,
                'configDir' => $target->configDir,
            ]),
            'mise-install-'.$runtime.'-'.$version,
            $target->siteId
        );
    }

    /**
     * @throws SSHError
     */
    public function uninstallRuntime(Site $site, string $runtime): void
    {
        $this->uninstallRuntimeForTarget(ToolingTargetDTO::forSite($site), $runtime);
    }

    /**
     * @throws SSHError
     */
    public function uninstallRuntimeForTarget(ToolingTargetDTO $target, string $runtime): void
    {
        $this->assertTarget($target);

        $this->server->ssh($target->user)->exec(
            view('ssh.mise.uninstall-runtime', [
                'runtime' => $runtime,
            ]),
            'mise-uninstall-'.$runtime,
            $target->siteId
        );
    }

    private function assertTarget(ToolingTargetDTO $target): void
    {
        if ($target->server->id !== $this->server->id) {
            throw new InvalidArgumentException('The tooling target must belong to the bound server.');
        }
    }
}
