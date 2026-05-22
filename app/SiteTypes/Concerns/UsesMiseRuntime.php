<?php

namespace App\SiteTypes\Concerns;

use App\Exceptions\SSHError;
use App\SSH\Mise\Mise;

trait UsesMiseRuntime
{
    /**
     * @var array<int, string>
     */
    public const SUPPORTED_NODE_VERSIONS = ['none', '16', '18', '20', '22'];

    /**
     * @throws SSHError
     */
    protected function setupNodeRuntime(string $runtime, string $version): void
    {
        $mise = new Mise($this->site->server);

        $mise->ensureInstalled();

        $mise->installRuntime($this->site, $runtime, $version);
    }

    protected function miseShimsPath(): string
    {
        $user = $this->site->user ?? $this->site->server->getSshUser();

        return '/home/'.$user.'/.local/share/mise/shims';
    }

    /**
     * @return array<string, string>
     */
    protected function workerEnvironment(): array
    {
        return [
            'PATH' => $this->shimPath(),
        ];
    }

    protected function shimPath(): string
    {
        $user = $this->site->user ?? $this->site->server->getSshUser();

        return $this->miseShimsPath().':/usr/local/bin:/usr/bin:/bin:/home/'.$user.'/.local/bin';
    }

    protected function wrapCommand(string $command, bool $cdToSitePath = false): string
    {
        $cdPath = $cdToSitePath && $this->site->path ? 'cd '.$this->site->path.' && ' : '';

        return "bash -c \"export PATH={$this->shimPath()} && {$cdPath}{$command}\"";
    }
}
