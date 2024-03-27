<?php

namespace App\SSH\Services\AddOnServices;

use App\SSH\Services\ServiceInterface;

abstract class AbstractAddOnService implements ServiceInterface
{
    abstract public function creationRules(array $input): array;

    abstract public function creationData(array $input): array;

    abstract public function create(): void;

    abstract public function delete(): void;

    abstract public function data(): array;
}
