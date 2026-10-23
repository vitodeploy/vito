<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\SourceControl;
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

        return $source;
    }
}
