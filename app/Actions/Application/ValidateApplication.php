<?php

namespace App\Actions\Application;

use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Models\Application;
use App\Models\Project;
use App\Models\SourceControl;
use App\Models\User;
use App\SourceControlProviders\SupportsApplications;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ValidateApplication
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function validate(Project $project, array $input, User $actor, ?Application $application = null): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', Rule::unique('applications')->where('project_id', $project->id)->ignore($application)],
        ])->validate();

        $provider = $this->provider($project, $input, $actor);

        try {
            $provider->getRepo($input['repository']);
        } catch (SourceControlIsNotConnected) {
            throw ValidationException::withMessages([
                'source_control_id' => 'Source control is not connected',
            ]);
        } catch (RepositoryPermissionDenied) {
            throw ValidationException::withMessages([
                'repository' => 'You do not have permission to access this repository',
            ]);
        } catch (RepositoryNotFound) {
            throw ValidationException::withMessages([
                'repository' => 'Repository not found',
            ]);
        }
    }

    /**
     * Validates the repository input and resolves the provider the actor may read it through.
     *
     * @param  array<string, mixed>  $input
     */
    public function provider(Project $project, array $input, User $actor): SupportsApplications
    {
        Validator::make($input, [
            'source_control_id' => ['integer', ...SourceControl::repositoryValidationRules($project->id)],
            'repository' => ['required', 'string', 'max:255', 'not_regex:~(?:\A|/)\.{1,2}(?:/|\z)~'],
        ])->validate();

        $sourceControl = SourceControl::query()->findOrFail($input['source_control_id']);
        Gate::forUser($actor)->authorize('view', $sourceControl);
        $provider = app(GetApplicationSourceControl::class)->provider($sourceControl);

        Validator::make($input, [
            'repository' => $provider->repositoryRules(),
        ])->validate();

        return $provider;
    }
}
