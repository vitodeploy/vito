<?php

namespace App\Services;

use App\Models\Service;

abstract class AbstractService implements ServiceInterface
{
    public function __construct(protected Service $service) {}

    public function creationRules(array $input): array
    {
        return [];
    }

    public function creationData(array $input): array
    {
        return [];
    }

    public function deletionRules(): array
    {
        return [];
    }

    public function data(): array
    {
        return [];
    }

    final public function install(): void
    {
        $this->doInstall();
        event('service.installed', $this->service);
    }

    final public function uninstall(): void
    {
        $this->doUninstall();
        event('service.uninstalled', $this->service);
    }

    protected function doInstall(): void
    {
        //
    }

    protected function doUninstall(): void
    {
        //
    }
}
