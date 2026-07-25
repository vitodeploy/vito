<?php

namespace App\Jobs\Network;

use App\Actions\Network\SyncProviderNetworks;
use App\Exceptions\PrivateNetworkPersistError;
use App\Exceptions\PrivateNetworkSyncError;
use App\Facades\Notifier;
use App\Models\Network;
use App\Models\Project;
use App\Notifications\GenericNotification;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    /**
     * The sweep is user-triggered from a button that only flashes "syncing", so a failure has
     * to be surfaced somewhere the user will see it rather than only in the job log.
     */
    public function failed(Throwable $e): void
    {
        Log::warning('Provider network sync job failed.', [
            'project_id' => $this->project->id,
            'network_id' => $this->network?->id,
            'exception' => $e::class,
            'reason' => $this->safeReason($e),
        ]);

        Notifier::send($this->project, new GenericNotification(
            __('Could not sync private networks from your cloud providers. Check the provider connection and its permissions.')
        ));
    }

    /**
     * Only the sweep's own exceptions are built to be credential-free. Anything else — a driver
     * or HTTP client exception, say — can carry a token or a connection string in its message,
     * and this log is written verbatim.
     */
    private function safeReason(Throwable $e): ?string
    {
        return $e instanceof PrivateNetworkSyncError || $e instanceof PrivateNetworkPersistError
            ? $e->getMessage()
            : null;
    }
}
