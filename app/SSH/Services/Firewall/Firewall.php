<?php

namespace App\SSH\Services\Firewall;

interface Firewall
{
    public function addRule(string $type, string $protocol, int $port, string $source, ?string $mask): void;

    public function removeRule(string $type, string $protocol, int $port, string $source, ?string $mask): void;
}
