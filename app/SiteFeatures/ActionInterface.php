<?php

namespace App\SiteFeatures;

use Illuminate\Http\Request;

interface ActionInterface
{
    public function name(): string;

    public function active(): bool;

    public function handle(Request $request): void;
}
