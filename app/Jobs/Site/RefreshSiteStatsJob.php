<?php

namespace App\Jobs\Site;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\ServerLog;
use App\Models\Site;
use App\Services\LogAnalysis\GoAccess\GoAccess;
use App\Traits\UniqueQueue;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class RefreshSiteStatsJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 600;

    public function __construct(protected Site $site)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->site->server_id}-site-{$this->site->id}-stats", function (): void {
            $base = GoAccess::BASE_DIR;
            $output = $this->site->server->ssh('root')->exec(
                'bash '.escapeshellarg("{$base}/bin/process.sh").' '.escapeshellarg("{$base}/sites/{$this->site->id}.conf"),
                'refresh-site-stats',
                $this->site->id
            );

            if (Str::contains($output, 'VITO_STATS_BUSY')) {
                return;
            }

            $now = Carbon::now();
            $months = [$now->format('Y-m'), $now->copy()->subMonthNoOverflow()->format('Y-m')];

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->site->server->project_id,
                type: 'site-stats.updated',
                data: [
                    'id' => $this->site->id,
                    'months' => $months,
                ],
            ));
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log(
            $this->site->server,
            'refresh-site-stats-failed',
            $e->getMessage(),
            $this->site
        );
    }
}
