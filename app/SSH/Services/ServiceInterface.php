<?php

namespace App\SSH\Services;

interface ServiceInterface
{
    public function creationRules(array $input): array;

    public function creationData(array $input): array;

    public function deletionRules(): array;

    public function data(): array;

    public function install(): void;

    public function uninstall(): void;
}
