<?php

namespace App\Jobs\Site;

use App\DTOs\SocketEventDTO;
use App\Enums\SiteStatus;
use App\Events\SocketEvent;
use App\Facades\Notifier;
use App\Http\Resources\SiteResource;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\ServerLog;
use App\Models\Site;
use App\Notifications\SiteInstallationFailed;
use App\Notifications\SiteInstallationSucceed;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Site $site) {}

    public function handle(): void
    {
        $this->run("server-{$this->site->server_id}", function () {
            $this->site->type()->install();
            $this->site->update([
                'status' => SiteStatus::READY,
                'progress' => 100,
            ]);
            $this->broadcastSiteUpdate();
            Notifier::send($this->site, new SiteInstallationSucceed($this->site));

            foreach ($this->site->hostedDomains as $hostedDomain) {
                dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');
            }
        });
    }

    public function failed(Exception $e): void
    {
        $this->site->status = SiteStatus::INSTALLATION_FAILED;
        $this->site->save();
        $this->broadcastSiteUpdate();
        ServerLog::log(
            $this->site->server,
            'site-installation-failed',
            $e->getMessage(),
            $this->site
        );
        Notifier::send($this->site, new SiteInstallationFailed($this->site));
    }

    private function broadcastSiteUpdate(): void
    {
        $this->site->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->site->server->project_id,
            type: 'site.updated',
            data: new SiteResource($this->site),
        ));
    }
}
