<?php

namespace App\ApplicationTypes;

use App\Models\Application;

abstract class AbstractApplicationType implements ApplicationType
{
    public function __construct(protected Application $application) {}

    public function createRules(array $input): array
    {
        return [];
    }

    public function createFields(array $input): array
    {
        return [];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function vhostTemplate(string $webserver): string
    {
        return '';
    }

    public function editRules(array $input): array
    {
        return [];
    }

    public function update(array $input): void {}
}
