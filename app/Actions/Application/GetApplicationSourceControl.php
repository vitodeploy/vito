<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\SourceControl;
use App\SourceControlProviders\SupportsApplications;
use Illuminate\Validation\ValidationException;

class GetApplicationSourceControl
{
    public function get(Application $application): SourceControl
    {
        $source = $application->sourceControl()->where(fn ($query) => $query
            ->whereNull('project_id')->orWhere('project_id', $application->project_id))->first();

        if (! $source) {
            throw ValidationException::withMessages(['deployment' => 'Select an available source control in this project before deploying.']);
        }

        $this->provider($source);

        return $source;
    }

    public function provider(SourceControl $sourceControl): SupportsApplications
    {
        $provider = $sourceControl->provider();
        if (! $provider instanceof SupportsApplications) {
            throw ValidationException::withMessages([
                'source_control_id' => 'This source-control provider supports Sites only. Update its plugin to support Applications.',
            ]);
        }

        return $provider;
    }
}
