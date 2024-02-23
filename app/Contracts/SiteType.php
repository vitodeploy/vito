<?php

namespace App\Contracts;

interface SiteType
{
    public function language(): string;

    public function supportedFeatures(): array;

    public function createValidationRules(array $input): array;

    public function createFields(array $input): array;

    public function data(array $input): array;

    public function install(): void;

    public function delete(): void;

    public function editValidationRules(array $input): array;

    public function edit(): void;
}
