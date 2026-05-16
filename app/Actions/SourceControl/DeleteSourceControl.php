<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use Illuminate\Validation\ValidationException;

class DeleteSourceControl
{
    public function delete(SourceControl $sourceControl): void
    {
        if ($sourceControl->isGithubApp()) {
            throw ValidationException::withMessages([
                'source_control' => __('GitHub App source controls cannot be deleted from Vito. Uninstall the app on GitHub instead.'),
            ]);
        }

        if ($sourceControl->sites()->exists()) {
            throw ValidationException::withMessages([
                'source_control' => __('This source control is being used by a site.'),
            ]);
        }

        $sourceControl->delete();
    }
}
