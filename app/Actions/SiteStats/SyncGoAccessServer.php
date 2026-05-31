<?php

namespace App\Actions\SiteStats;

use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\ServerLog;
use App\Services\LogAnalysis\GoAccess\GoAccess;

class SyncGoAccessServer
{
    /**
     * @throws SSHError
     */
    public function sync(Server $server, ?ServerLog $log = null): void
    {
        $service = $server->service('log_analysis');
        if (! $service) {
            return;
        }

        $base = GoAccess::BASE_DIR;
        $ssh = $server->ssh();
        if ($log) {
            $ssh->setLog($log);
        }

        $ssh->exec(
            'sudo mkdir -p '
            .escapeshellarg("{$base}/bin").' '
            .escapeshellarg("{$base}/sites").' '
            .escapeshellarg("{$base}/data"),
            'goaccess-mkdir'
        );

        $ssh->write("{$base}/bin/run.sh", view('ssh.services.log_analysis.goaccess.bin.run', [
            'baseDir' => $base,
        ]), 'root');
        $ssh->write("{$base}/bin/process.sh", view('ssh.services.log_analysis.goaccess.bin.process', [
            'baseDir' => $base,
            'scriptVersion' => GoAccess::SCRIPT_VERSION,
        ]), 'root');

        $renderer = app(RenderSiteStatsConf::class);
        $webserver = $server->webserver();
        $webserverId = $webserver ? $webserver->handler()::id() : 'nginx';
        $retention = (int) ($service->type_data['data_retention'] ?? 12);

        foreach ($server->sites as $site) {
            $site->setRelation('server', $server);

            if (! $site->statsEnabled()) {
                $ssh->exec('sudo rm -f '.escapeshellarg("{$base}/sites/{$site->id}.conf"), 'goaccess-skip-disabled');

                continue;
            }

            $ssh->write("{$base}/sites/{$site->id}.conf", $renderer->render($site, $webserverId, $retention), 'root');
        }

        $this->ensureCron($server);

        $service->type_data = array_merge($service->type_data ?? [], [
            'script_version' => GoAccess::SCRIPT_VERSION,
            'conf_version' => GoAccess::CONF_VERSION,
        ]);
        $service->save();
    }

    private function ensureCron(Server $server): void
    {
        $cron = $server->cronJobs()
            ->where('user', 'root')
            ->where('command', GoAccess::CRON_COMMAND)
            ->first();

        if (! $cron) {
            $cron = $server->cronJobs()->create([
                'site_id' => null,
                'name' => GoAccess::CRON_NAME,
                'user' => 'root',
                'command' => GoAccess::CRON_COMMAND,
                'frequency' => GoAccess::CRON_FREQUENCY,
                'status' => CronjobStatus::READY,
            ]);
        } elseif (! in_array($cron->status, [CronjobStatus::READY, CronjobStatus::DISABLED], true)) {
            $cron->update(['status' => CronjobStatus::READY]);
        }

        $server->cron()->update('root', CronJob::crontab($server, 'root'));
    }
}
