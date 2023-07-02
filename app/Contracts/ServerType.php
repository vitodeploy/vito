<?php

namespace App\Contracts;

interface ServerType
{
    public function createValidationRules(array $input): array;

    public function data(array $input): array;

    public function createServices(array $input): void;

    public function install(): void;
}
