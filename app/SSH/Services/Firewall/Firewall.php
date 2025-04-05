<?php

namespace App\SSH\Services\Firewall;

use App\SSH\Services\ServiceInterface;

interface Firewall extends ServiceInterface
{
    public function applyRules(): void;
}
