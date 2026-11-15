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

    public int $timeout = 300;

    public function __construct(
        protected Project $project,
        protected ?Network $network = null,
    ) {}

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

    private function safeReason(Throwable $e): ?string
    {
        return $e instanceof PrivateNetworkSyncError || $e instanceof PrivateNetworkPersistError
            ? $e->getMessage()
            : null;
    }
}
