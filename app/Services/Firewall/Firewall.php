<?php

namespace App\Services\Firewall;

use App\Services\ServiceInterface;

interface Firewall extends ServiceInterface
{
    public function applyRules(): void;
}
