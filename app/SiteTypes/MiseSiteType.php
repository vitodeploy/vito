<?php

namespace App\SiteTypes;

use App\Exceptions\SSHError;
use App\Helpers\SiteShellEnvironment;
use App\Tooling\ToolingRegistry;

abstract class MiseSiteType extends AbstractSiteType
{
    public static function supportsTooling(): bool
    {
        return true;
    }

    abstract protected function runtime(): string;

    abstract protected function runtimeVersion(): string;

    /**
     * @throws SSHError
     */
    protected function setupRuntime(): void
    {
        $tool = ToolingRegistry::find($this->runtime());

        $tool?->install($this->site, $this->runtimeVersion());
    }

    /**
     * @return array<string, string>
     */
    protected function workerEnvironment(): array
    {
        return SiteShellEnvironment::collect($this->site);
    }

    protected function workerCommand(): string
    {
        return $this->startCommand();
    }

    abstract protected function startCommand(): string;
}
