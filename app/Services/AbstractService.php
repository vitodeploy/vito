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

    public function install(): void
    {
        //
    }

    public function uninstall(): void
    {
        //
    }

    public function version(): string
    {
        return $this->service->version;
    }

    public function canBeManaged(): bool
    {
        return (bool) $this->unit();
    }

    public function manage(string $action): bool
    {
        $expectedState = in_array($action, ['stop', 'disable'], true) ? 'Active: inactive' : 'Active: active';

        $status = $this->service->server->systemd()->{$action}($this->unit());

        return str($status)->contains($expectedState);
    }
}
