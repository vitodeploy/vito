<?php

namespace App\Actions\Site\Tooling;

use App\Models\Component;
use App\Models\Site;
use App\Tooling\ToolingRegistry;
use Illuminate\Validation\ValidationException;

class AssertToolingNotUsedByComponents
{
    public function assert(Site $site, string $toolId): void
    {
        if (Component::query()->where('server_id', $site->server_id)->where('user', $site->user)
            ->with('environment.application')->get()
            ->contains(fn (Component $component): bool => $component->environment->application->preset()->tooling() === $toolId)) {
            $label = ToolingRegistry::find($toolId)?->label() ?? $toolId;

            throw ValidationException::withMessages([
                'tool' => "{$label} is required by a component sharing this isolated user and cannot be uninstalled.",
            ]);
        }
    }
}
