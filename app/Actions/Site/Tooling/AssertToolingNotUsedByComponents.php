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
            ->contains(function (Component $component) use ($toolId): bool {
                $preset = $component->environment->application->preset();

                return $preset->tooling() === $toolId && $preset->sharedTooling();
            })) {
            $label = ToolingRegistry::find($toolId)?->label() ?? $toolId;

            throw ValidationException::withMessages([
                'tool' => "{$label} is required by a component sharing this isolated user and cannot be uninstalled.",
            ]);
        }
    }
}
