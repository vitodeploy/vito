<?php

namespace App\SiteTypes;

use App\Exceptions\SSHError;
use App\SiteTypes\Concerns\UsesMiseRuntime;

abstract class MiseSiteType extends AbstractSiteType
{
    use UsesMiseRuntime;

    abstract protected function runtime(): string;

    abstract protected function runtimeVersion(): string;

    /**
     * @throws SSHError
     */
    protected function setupRuntime(): void
    {
        $this->setupMiseRuntime($this->runtime(), $this->runtimeVersion());
    }

    protected function workerCommand(): string
    {
        return $this->startCommand();
    }

    abstract protected function startCommand(): string;
}
