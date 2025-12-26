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

    protected function runtimePrefix(): string
    {
        return 'mise exec '.$this->runtime().'@'.$this->runtimeVersion().' --';
    }
}
