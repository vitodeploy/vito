<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DeleteProject
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function delete(User $user, Project $project, array $input): void
    {
        Validator::make($input, [
            'name' => 'required',
        ])->validate();

        if ($user->projects()->count() === 1) {
            throw ValidationException::withMessages([
                'name' => __('Cannot delete the last project.'),
            ]);
        }

        if ($user->current_project_id == $project->id) {
            throw ValidationException::withMessages([
                'name' => __('Cannot delete your current project.'),
            ]);
        }

        /** @var Project $randomProject */
        $randomProject = $user->projects()->where('project_id', '!=', $project->id)->first();
        $user->current_project_id = $randomProject->id;
        $user->save();

        $project->delete();
    }
}
