<?php

namespace App\Jobs\Network;

use App\Actions\Network\SyncProviderNetworks;
use App\Models\Network;
use App\Models\Project;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncProviderNetworksJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    /**
     * The `default` Horizon supervisor runs a 90s timeout. A sweep across several
     * connections and paginated pages exceeds that, and a SIGKILLed worker never runs
     * `UniqueQueue`'s `finally`, so the lock would survive for its full duration and
     * block every later sync. Declared here because Laravel prefers the job's own
     * timeout over the supervisor's.
     */
    public int $timeout = 300;

    public function __construct(
        protected Project $project,
        protected ?Network $network = null,
    ) {}

    /**
     * `UniqueQueue` serialises rather than de-duplicates — a contended job releases and
     * retries, so repeated clicks would each run a full provider sweep and invite 429s.
     * De-duplication therefore has to happen before dispatch.
     */
    public static function dispatchUnlessRecent(Project $project, ?Network $network = null): bool
    {
        $scope = $network instanceof Network ? (string) $network->id : 'all';
        $key = 'provider-networks:'.$project->id.':'.$scope;

        if (! Cache::add($key, true, 30)) {
            return false;
        }

        dispatch(new self($project, $network));

        return true;
    }

    protected function lockSeconds(): int
    {
        return $this->timeout + 60;
    }

    public function handle(): void
    {
        $this->run("provider-networks-{$this->project->id}", function (): void {
            app(SyncProviderNetworks::class)->forProject($this->project, $this->network);
        });
    }

    public function failed(Exception $e): void
    {
        Log::warning('Provider network sync job failed.', [
            'project_id' => $this->project->id,
            'network_id' => $this->network?->id,
        ]);
    }
}
