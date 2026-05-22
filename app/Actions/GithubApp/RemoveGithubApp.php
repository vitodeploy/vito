<?php

namespace App\Actions\GithubApp;

use App\Actions\Bootstrap\GetBootstrap;
use App\Models\GithubApp;
use App\Models\SourceControl;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveGithubApp
{
    public function remove(): void
    {
        $hasSites = SourceControl::query()
            ->withTrashed()
            ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
            ->whereHas('sites')
            ->exists();

        if ($hasSites) {
            throw ValidationException::withMessages([
                'github_app' => __('Cannot remove the GitHub App while sites are still attached to its installations.'),
            ]);
        }

        DB::transaction(function (): void {
            SourceControl::query()
                ->withTrashed()
                ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
                ->get()
                ->each(fn (SourceControl $sc) => $sc->forceDelete());

            GithubApp::query()->delete();
        });

        GetBootstrap::forgetVersion();
    }
}
