<?php

namespace App\Actions\Application;

use App\Actions\Environment\CreateEnvironment;
use App\Models\Application;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateApplication
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Project $project, array $input, User $actor): Application
    {
        Validator::make($input, [
            'runtime' => ['required', 'string', Rule::in(array_keys(config('component.runtimes', [])))],
            'environment' => ['required', 'array'],
        ])->validate();

        app(ValidateApplication::class)->validate($project, $input, $actor);

        return DB::transaction(function () use ($project, $input): Application {
            $application = $project->applications()->create([
                'name' => $input['name'],
                'source_control_id' => $input['source_control_id'],
                'repository' => $input['repository'],
                'runtime' => $input['runtime'],
            ]);

            try {
                app(CreateEnvironment::class)->create($application, $input['environment']);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages(
                    collect($e->errors())->mapWithKeys(fn (array $messages, string $field): array => ['environment.'.$field => $messages])->all()
                );
            }

            return $application;
        });
    }
}
