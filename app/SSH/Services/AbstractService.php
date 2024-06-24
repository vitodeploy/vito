<?php

namespace App\SSH\Services;

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
}
