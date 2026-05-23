<?php

namespace App\Actions\Site\Tooling;

use App\Jobs\Site\Tooling\UninstallSiteToolingJob;
use App\Models\Site;
use App\Tooling\SiteToolingState;
use App\Tooling\ToolingRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UninstallSiteTooling
{
    public function uninstall(Site $site, string $toolId): void
    {
        $tool = ToolingRegistry::find($toolId);

        if (! $tool) {
            Validator::make(['tool' => $toolId], ['tool' => 'in:'.implode(',', ToolingRegistry::ids())])->validate();
        }

        if ($tool->installedVersion($site) === null) {
            throw ValidationException::withMessages([
                'tool' => "{$tool::label()} is not currently installed for this isolated user.",
            ]);
        }

        $current = SiteToolingState::currentStatus($site, $toolId);
        if ($current === SiteToolingState::STATUS_INSTALLING || $current === SiteToolingState::STATUS_UNINSTALLING) {
            throw ValidationException::withMessages([
                'tool' => "{$tool::label()} is currently {$current}; please wait for the operation to complete.",
            ]);
        }

        SiteToolingState::setStatus($site, $toolId, SiteToolingState::STATUS_UNINSTALLING);

        dispatch(new UninstallSiteToolingJob($site, $toolId));
    }
}
