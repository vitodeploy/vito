<?php

namespace App\Actions\GitHook;

use App\Jobs\EnvironmentDeployment\TriggerEnvironmentDeployFromWebhookJob;
use App\Models\Environment;
use App\Models\GitHook;
use App\Models\SourceControl;
use Illuminate\Database\Eloquent\Builder;

class TriggerRepositoryAutoDeploy
{
    public function forBranch(GitHook $hook, string $branch): void
    {
        if ($hook->repository !== null && in_array('deploy', $hook->actions, true) && $hook->sourceControl) {
            $this->trigger($hook->sourceControl, $hook->repository(), $branch);
        }
    }

    /** @return Builder<Environment> */
    public function matching(int $sourceControlId, string $repository, ?string $branch = null): Builder
    {
        return Environment::query()->where('auto_deploy', true)->when($branch !== null, fn (Builder $query) => $query->where('branch', $branch))
            ->whereHas('application', fn (Builder $query) => $query->where('source_control_id', $sourceControlId)
                ->where('repository', $repository)->whereHas('sourceControl', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->whereNull('project_id')->orWhereColumn('source_controls.project_id', 'applications.project_id'))));
    }

    public function trigger(SourceControl $sourceControl, string $repository, string $branch): void
    {
        if ($branch === '') {
            return;
        }
        $this->matching($sourceControl->id, $repository, $branch)->each(function (Environment $environment) use ($sourceControl, $repository, $branch): void {
            TriggerEnvironmentDeployFromWebhookJob::dispatch($environment->id, $sourceControl->id, $repository, $branch);
        });
    }
}
