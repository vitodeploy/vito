<?php

namespace App\SSH\Services\Firewall;

use App\SSH\Services\ServiceInterface;

interface Firewall extends ServiceInterface
{
    public function addRule(string $type, string $protocol, int $port, string $source, ?string $mask): void;

    public function removeRule(string $type, string $protocol, int $port, string $source, ?string $mask): void;
}
