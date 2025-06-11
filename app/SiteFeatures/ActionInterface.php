<?php

namespace App\SiteFeatures;

use App\DTOs\DynamicForm;
use Illuminate\Http\Request;

interface ActionInterface
{
    public function name(): string;

    public function active(): bool;

    public function form(): ?DynamicForm;

    public function handle(Request $request): void;
}
