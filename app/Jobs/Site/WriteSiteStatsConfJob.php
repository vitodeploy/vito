<?php

namespace App\Jobs\Site;

use App\Actions\SiteStats\RenderSiteStatsConf;
use App\Models\ServerLog;
use App\Models\Site;
use App\Services\LogAnalysis\GoAccess\GoAccess;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WriteSiteStatsConfJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 120;

    public function __construct(protected Site $site)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        if (! $this->site->statsEnabled()) {
            return;
        }

        $this->run("server-{$this->site->server_id}-site-stats", function (): void {
            $base = GoAccess::BASE_DIR;
            $ssh = $this->site->server->ssh();
            $ssh->exec("sudo mkdir -p {$base}/sites {$base}/data", 'goaccess-mkdir');
            $ssh->write(
                "{$base}/sites/{$this->site->id}.conf",
                app(RenderSiteStatsConf::class)->render($this->site),
                'root'
            );
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log(
            $this->site->server,
            'write-site-stats-conf-failed',
            $e->getMessage(),
            $this->site
        );
    }
}
