<?php

namespace App\Services;

use App\Exceptions\SSHError;
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

    public function versionCommand(): ?string
    {
        return null;
    }

    public function parseVersionOutput(string $output): ?string
    {
        $version = trim($output);

        return $version === '' ? null : $version;
    }

    /**
     * @throws SSHError
     */
    public function version(): string
    {
        $command = $this->versionCommand();

        if ($command === null) {
            return $this->service->version;
        }

        $output = $this->service->server->ssh()->exec($command);

        return $this->parseVersionOutput($output) ?? trim($output);
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
