<?php

namespace App\ServerTypes;

interface ServerType
{
    public function createRules(array $input): array;

    public function data(array $input): array;

    public function createServices(array $input): void;

    public function install(): void;
}
