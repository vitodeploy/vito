<?php

namespace App\Actions\SiteStats;

use App\Enums\CronjobStatus;
use App\Exceptions\SSHError;
use App\Models\CronJob;
use App\Models\Server;
use App\Services\LogAnalysis\GoAccess\GoAccess;

class SyncGoAccessServer
{
    /**
     * @throws SSHError
     */
    public function sync(Server $server): void
    {
        $service = $server->service('log_analysis');
        if (! $service) {
            return;
        }

        $base = GoAccess::BASE_DIR;
        $ssh = $server->ssh();

        $ssh->exec("sudo mkdir -p {$base}/bin {$base}/sites {$base}/data", 'goaccess-mkdir');

        $ssh->write("{$base}/bin/run.sh", view('ssh.services.log_analysis.goaccess.bin.run', [
            'baseDir' => $base,
        ]), 'root');
        $ssh->write("{$base}/bin/process.sh", view('ssh.services.log_analysis.goaccess.bin.process', [
            'baseDir' => $base,
            'scriptVersion' => GoAccess::SCRIPT_VERSION,
        ]), 'root');

        $renderer = app(RenderSiteStatsConf::class);
        foreach ($server->sites()->with('server.services')->get() as $site) {
            $site->setRelation('server', $server);

            if (! $site->statsEnabled()) {
                $ssh->exec('sudo rm -f '.escapeshellarg("{$base}/sites/{$site->id}.conf"), 'goaccess-skip-disabled');

                continue;
            }

            $ssh->write("{$base}/sites/{$site->id}.conf", $renderer->render($site), 'root');
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
            ->where('hidden', true)
            ->where('command', GoAccess::CRON_COMMAND)
            ->first();

        if (! $cron) {
            $server->cronJobs()->create([
                'site_id' => null,
                'user' => 'root',
                'command' => GoAccess::CRON_COMMAND,
                'frequency' => GoAccess::CRON_FREQUENCY,
                'hidden' => true,
                'status' => CronjobStatus::READY,
            ]);
        } elseif ($cron->status !== CronjobStatus::READY) {
            $cron->update(['status' => CronjobStatus::READY]);
        }

        $server->cron()->update('root', CronJob::crontab($server, 'root'));
    }
}
