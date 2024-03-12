<?php

namespace App\SiteTypes;

interface SiteType
{
    public function language(): string;

    public function supportedFeatures(): array;

    public function createRules(array $input): array;

    public function createFields(array $input): array;

    public function data(array $input): array;

    public function install(): void;

    public function editRules(array $input): array;

    public function edit(): void;
}
