<?php

namespace App\Actions\GithubApp;

use App\Models\SourceControl;

class EditGithubAppSourceControl
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(SourceControl $sourceControl, array $input, ?int $projectId): SourceControl
    {
        $sourceControl->project_id = $projectId;

        $sourceControl->save();

        return $sourceControl;
    }
}
