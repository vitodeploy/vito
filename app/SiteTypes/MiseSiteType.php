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

    protected function misePathExport(): string
    {
        return 'export PATH='.$this->miseShimsPath().':$PATH';
    }

    protected function runtimePrefix(bool $withPath = true): string
    {
        return sprintf(
            '%s && mise exec %s'.$this->runtime().'@'.$this->runtimeVersion().' --verbose --',
            $this->misePathExport(),
            $withPath ? '-C '.$this->site->path.' ' : '',
        );
    }
}
