<?php

namespace App\SiteTypes;

use App\Exceptions\SSHError;
use App\SSH\Mise\Mise;

abstract class MiseSiteType extends AbstractSiteType
{
    abstract protected function runtime(): string;

    abstract protected function runtimeVersion(): ?string;

    /**
     * @throws SSHError
     */
    protected function setupRuntime(): void
    {
        $mise = new Mise($this->site->server);

        $mise->ensureInstalled();

        $version = $this->runtimeVersion();
        if ($version) {
            $mise->installRuntime(
                $this->site,
                $this->runtime(),
                $version
            );
        }
    }

    protected function runtimePrefix(): string
    {
        $version = $this->runtimeVersion();

        return 'mise exec '.$this->runtime().'@'.$version.' --';
    }

    public function requiredServices(): array
    {
        return [
            'webserver',
            'process_manager',
        ];
    }
}
