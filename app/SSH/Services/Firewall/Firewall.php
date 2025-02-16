<?php

namespace App\SSH\Services\Firewall;

interface Firewall
{
    public function applyRules(): void;
}
