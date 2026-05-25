<?php

namespace App\Actions\Site\Tooling;

use App\Jobs\Site\Tooling\InstallSiteToolingJob;
use App\Models\IsolatedUser;
use App\Models\Site;
use App\Tooling\SiteToolingState;
use App\Tooling\ToolingRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallSiteTooling
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function install(Site $site, string $toolId, array $input): void
    {
        $tool = ToolingRegistry::find($toolId);

        if (! $tool) {
            Validator::make(['tool' => $toolId], ['tool' => 'in:'.implode(',', ToolingRegistry::ids())])->validate();
        }

        Validator::make($input, [
            'version' => [
                'required',
                'string',
                Rule::in($tool::supportedVersions()),
            ],
        ])->validate();

        $iuser = $site->isolatedUser;
        if (! $iuser instanceof IsolatedUser) {
            throw ValidationException::withMessages([
                'tool' => 'Tooling can only be installed on isolated sites.',
            ]);
        }

        $current = $iuser->toolingStatus($toolId);
        if ($current === SiteToolingState::STATUS_INSTALLING || $current === SiteToolingState::STATUS_UNINSTALLING) {
            throw ValidationException::withMessages([
                'tool' => "{$tool::label()} is currently {$current}; please wait for the operation to complete.",
            ]);
        }

        SiteToolingState::setStatus($site, $toolId, SiteToolingState::STATUS_INSTALLING);

        dispatch(new InstallSiteToolingJob($site, $toolId, $input['version']));
    }
}
