<?php

namespace App\Jobs\Site;

use App\Enums\SiteStatus;
use App\Facades\Notifier;
use App\Models\Site;
use App\Notifications\SiteInstallationFailed;
use App\Notifications\SiteInstallationSucceed;
use App\Traits\UniqueQueue;
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
            Notifier::send($this->site, new SiteInstallationSucceed($this->site));
        });
    }

    public function failed(): void
    {
        $this->site->status = SiteStatus::INSTALLATION_FAILED;
        $this->site->save();
        Notifier::send($this->site, new SiteInstallationFailed($this->site));
    }
}
