<?php

namespace App\Actions\GitHook;

use App\Jobs\GitHook\SyncRepositoryGitHookJob;
use App\Models\Application;
use App\Models\Environment;
use App\Models\GitHook;
use App\Models\Project;
use App\Models\SourceControl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRepositoryGitHook
{
    public function sync(SourceControl $sourceControl, string $repository): void
    {
        if ($sourceControl->isGithubApp()) {
            return;
        }
        Cache::lock('repository-hook-'.$sourceControl->id.'-'.hash('sha256', $repository), 120)->block(10, function () use ($sourceControl, $repository): void {
            $hook = GitHook::query()->where('source_control_id', $sourceControl->id)->where('repository', $repository)->first();
            $needed = app(TriggerRepositoryAutoDeploy::class)->matching($sourceControl->id, $repository)->exists();
            if (! $needed) {
                if ($hook) {
                    if ($hook->hook_id) {
                        $sourceControl->provider()->destroyHook($repository, $hook->hook_id);
                    }
                    $hook->delete();
                }

                return;
            }
            if (! $hook) {
                $hook = new GitHook([
                    'source_control_id' => $sourceControl->id, 'secret' => Str::random(64),
                    'events' => ['push'], 'actions' => ['deploy'],
                ]);
                $hook->repository = $repository;
                $hook->save();
            }
            if (! $hook->hook_id) {
                $hook->deployHook();
            }
        });
    }

    public function disable(Environment $environment): void
    {
        if ($environment->auto_deploy) {
            $environment->auto_deploy = false;
            $environment->save();
        }
        $source = $environment->application->sourceControl;
        $repository = $environment->application->repository;
        if ($source) {
            DB::afterCommit(fn () => $this->sync($source, $repository));
        }
    }

    public function disableProject(Project $project, bool $afterCommit = true): void
    {
        Environment::query()->whereHas('application', fn ($query) => $query->where('project_id', $project->id))->update(['auto_deploy' => false]);
        foreach ($project->applications()->with('sourceControl')->get()->unique(fn ($application) => $application->source_control_id.':'.$application->repository) as $application) {
            if ($application->sourceControl) {
                if ($afterCommit) {
                    DB::afterCommit(fn () => $this->sync($application->sourceControl, $application->repository));
                } else {
                    $this->sync($application->sourceControl, $application->repository);
                }
            }
        }
    }

    public function reconcile(): void
    {
        $pairs = Application::query()->whereHas('environments', fn ($query) => $query->where('auto_deploy', true))
            ->select('source_control_id', 'repository')->union(GitHook::query()->whereNotNull('repository')->select('source_control_id', 'repository'))->get();
        foreach ($pairs as $pair) {
            if ($pair->source_control_id) {
                SyncRepositoryGitHookJob::dispatch($pair->source_control_id, $pair->repository);
            }
        }
    }
}
