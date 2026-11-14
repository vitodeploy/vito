<?php

namespace App\Actions\Projects;

use App\Actions\Component\PurgeServerComponents;
use App\Actions\GitHook\SyncRepositoryGitHook;
use App\Models\Environment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(fn (): Project => $this->validateDeletion($project));
        app(SyncRepositoryGitHook::class)->disableProject($project, afterCommit: false);

        DB::transaction(function () use ($project): void {
            $project = $this->validateDeletion($project);
            $project->delete();
        });

        $user->ensureHasDefaultProject();
    }

    private function validateDeletion(Project $project): Project
    {
        $project = Project::query()->lockForUpdate()->findOrFail($project->id);
        $applications = $project->applications()->orderBy('id')->lockForUpdate()->get();
        Environment::query()->whereIn('application_id', $applications->modelKeys())->orderBy('id')->lockForUpdate()->get();
        $servers = $project->servers()->orderBy('id')->lockForUpdate()->get();
        foreach ($servers as $server) {
            app(PurgeServerComponents::class)->validate($server);
        }

        return $project;
    }
}
