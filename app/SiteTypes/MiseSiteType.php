<?php

namespace App\SiteTypes;

use App\Exceptions\SSHError;
use App\SSH\Mise\Mise;

abstract class MiseSiteType extends AbstractSiteType
{
    abstract protected function runtime(): string;

    abstract protected function runtimeVersion(): string;

    /**
     * @throws SSHError
     */
    protected function setupRuntime(): void
    {
        $mise = new Mise($this->site->server);

        $mise->ensureInstalled();

        $mise->installRuntime(
            $this->site,
            $this->runtime(),
            $this->runtimeVersion()
        );
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

    protected function workerCommand(): string
    {
        return $this->startCommand();
    }

    abstract protected function startCommand(): string;

    protected function wrapCommand(string $command, bool $cdToSitePath = false): string
    {
        $cdPath = $cdToSitePath && $this->site->path ? 'cd '.$this->site->path.' && ' : '';

        return "bash -c \"export PATH={$this->shimPath()} && {$cdPath}{$command}\"";
    }
}
