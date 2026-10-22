<?php

namespace App\Actions\Environment;

use App\Actions\Component\GetComponentOptions;
use App\Actions\EnvironmentDomain\GetEnvironmentDomainOptions;
use App\Http\Resources\ComponentResource;
use App\Http\Resources\DeploymentScriptResource;
use App\Http\Resources\EnvironmentAttachmentResource;
use App\Http\Resources\EnvironmentDeploymentResource;
use App\Http\Resources\EnvironmentDomainResource;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Tables\Applications\ComponentCronJobTable;
use App\Tables\Applications\ComponentWorkerTable;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class GetEnvironmentOverview
{
    /**
     * Resolves a component of the environment whose server still belongs to the application's project.
     */
    public function component(Environment $environment, ?int $id): ?Component
    {
        return $id ? $environment->components()->whereHas('server', $this->inProject($environment))->find($id) : null;
    }

    /**
     * Resolves a resource attachment of the environment whose server still belongs to the application's project.
     */
    public function attachment(Environment $environment, ?int $id): ?EnvironmentAttachment
    {
        return $id ? $environment->attachments()->whereHas('server', $this->inProject($environment))->find($id) : null;
    }

    /**
     * Lazy props for the environment graph and its side panel, so partial reloads only compute what they request.
     *
     * @return array<string, Closure>
     */
    public function props(Environment $environment, ?Component $component, ?EnvironmentAttachment $attachment): array
    {
        return [
            'topology' => fn () => app(GetEnvironmentTopology::class)->get($environment),
            'lastDeployment' => fn () => ($run = $environment->deployments()->latest('id')->first()) ? new EnvironmentDeploymentResource($run) : null,
            'warnings' => fn () => $environment->application->preset()->warnings($environment),
            'primaryCandidates' => fn () => app(GetComponentOptions::class)->primaryCandidates($environment),
            'deploymentActive' => fn () => $environment->deployments()->blocking()->exists(),
            'capabilities' => fn () => $component ? app(GetComponentOptions::class)->capabilities($environment) : [],
            'domainOptions' => fn () => $component ? app(GetEnvironmentDomainOptions::class)->get($environment) : null,
            'component' => fn () => $component ? new ComponentResource($component->load('server', 'capabilities', 'backends.targetComponent')) : null,
            'componentDomains' => fn () => $component ? EnvironmentDomainResource::collection($component->domains()->orderBy('id')->get()) : [],
            'componentScripts' => fn () => $component ? DeploymentScriptResource::collection($component->deploymentScripts()->get()) : [],
            'inheritedScripts' => fn () => $component ? DeploymentScriptResource::collection($environment->deploymentScripts()->get()) : [],
            'workers' => fn () => $component ? ComponentWorkerTable::make($component->workers())->identifier('workers')->simplePaginate() : null,
            'cronjobs' => fn () => $component ? ComponentCronJobTable::make($component->cronJobs())->identifier('cronjobs')->simplePaginate() : null,
            'backendTargets' => fn () => $component ? app(GetComponentOptions::class)->backendTargets($environment) : [],
            'resource' => fn () => $attachment ? new EnvironmentAttachmentResource($attachment->load('server', 'attachable')) : null,
        ];
    }

    /**
     * @return Closure(Builder<\App\Models\Server>): Builder<\App\Models\Server>
     */
    private function inProject(Environment $environment): Closure
    {
        return fn (Builder $query): Builder => $query->where('project_id', $environment->application->project_id);
    }
}
