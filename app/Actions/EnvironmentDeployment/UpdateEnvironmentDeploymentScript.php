<?php

namespace App\Actions\EnvironmentDeployment;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Enums\ApplicationStatus;
use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Component;
use App\Models\DeploymentScript;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateEnvironmentDeploymentScript
{
    /** @param array<string, mixed> $input */
    public function update(Environment $environment, array $input, ?Component $component = null): ?DeploymentScript
    {
        Validator::make($input, [
            'stage' => ['required', Rule::in(['build', 'pre-release', 'post-release'])],
            'content' => ['present', 'nullable', 'string', 'max:100000', 'not_regex:/\x00/'],
            'inherit' => ['sometimes', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($environment, $input, $component): ?DeploymentScript {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            app(AssertEnvironmentIdle::class)->validate($environment, 'content');
            if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY
                || ($component && ($component->environment_id !== $environment->id || $component->type !== \App\Enums\ComponentType::APP))) {
                throw ValidationException::withMessages(['content' => 'This deployment script cannot be changed.']);
            }
            $scripts = ($component ?? $environment)->deploymentScripts();
            if ($component && ($input['inherit'] ?? false)) {
                $scripts->where('name', $input['stage'])->delete();
                app(MarkEnvironmentForRedeploy::class)->mark($environment);

                return null;
            }
            $script = $scripts->updateOrCreate(['name' => $input['stage']], ['content' => $input['content'] ?? '']);
            app(MarkEnvironmentForRedeploy::class)->mark($environment);

            return $script;
        });
    }
}
