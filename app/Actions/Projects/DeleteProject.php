<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteProject
{
    public function delete(User $user, int $projectId): void
    {
        /** @var Project $project */
        $project = $user->projects()->findOrFail($projectId);

        if ($user->projects()->count() === 1) {
            throw ValidationException::withMessages([
                'project' => __('Cannot delete the last project.'),
            ]);
        }

        if ($user->current_project_id == $project->id) {
            /** @var Project $randomProject */
            $randomProject = $user->projects()->where('id', '!=', $project->id)->first();
            $user->current_project_id = $randomProject->id;
            $user->save();
        }

        $project->delete();
    }
}
