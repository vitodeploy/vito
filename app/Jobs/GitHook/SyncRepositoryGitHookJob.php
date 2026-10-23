<?php

namespace App\Jobs\GitHook;

use App\Actions\GitHook\SyncRepositoryGitHook;
use App\Models\SourceControl;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncRepositoryGitHookJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 90;

    public bool $failOnTimeout = true;

    public function __construct(public int $sourceControlId, public string $repository) {}

    public function handle(): void
    {
        $this->run('repository-hook-'.$this->sourceControlId.'-'.hash('sha256', $this->repository), function (): void {
            $source = SourceControl::query()->find($this->sourceControlId);
            if ($source) {
                app(SyncRepositoryGitHook::class)->sync($source, $this->repository);
            }
        });
    }

    public function failed(Exception $e): void
    {
        Log::error('Repository hook synchronization failed; scheduled reconciliation will retry.', ['source_control_id' => $this->sourceControlId]);
    }
}
