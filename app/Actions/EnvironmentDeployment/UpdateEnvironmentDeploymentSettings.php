<?php

namespace App\Actions\EnvironmentDeployment;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Enums\ApplicationStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateEnvironmentDeploymentSettings
{
    /** @param array<string, mixed> $input */
    public function update(Environment $environment, array $input): void
    {
        Validator::make($input, [
            'shared_paths' => ['present', 'array', 'list', 'max:100'],
            'shared_paths.*' => ['required', 'string', 'max:255', 'distinct', 'regex:/\A(?!\/)(?!.*(?:\A|\/)\.{1,2}(?:\/|\z))(?!.*(?:\A|\/)\.env(?:[.\/-]|\z))[a-zA-Z0-9_.-]+(?:\/[a-zA-Z0-9_.-]+)*\z/'],
            'keep_releases' => ['required', 'integer', 'min:1', 'max:100'],
        ])->validate();
        foreach ($input['shared_paths'] as $path) {
            foreach ($input['shared_paths'] as $other) {
                if (str_starts_with($path, $other.'/')) {
                    throw ValidationException::withMessages(['shared_paths' => 'Shared paths cannot contain one another.']);
                }
            }
        }
        DB::transaction(function () use ($environment, $input): void {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            app(AssertEnvironmentIdle::class)->validate($environment, 'shared_paths');
            if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY) {
                throw ValidationException::withMessages(['shared_paths' => 'Deployment settings cannot change during teardown.']);
            }
            $environment->shared_paths = $input['shared_paths'];
            $environment->keep_releases = $input['keep_releases'];
            $environment->save();
            app(MarkEnvironmentForRedeploy::class)->mark($environment);
        });
    }
}
