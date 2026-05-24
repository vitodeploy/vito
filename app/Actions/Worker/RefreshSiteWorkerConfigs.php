<?php

namespace App\Actions\Worker;

use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\SiteTypes\AbstractSiteType;
use App\Tooling\ToolingRegistry;
use Throwable;

final class RefreshSiteWorkerConfigs
{
    public function refresh(Site $origin, string $changedToolId): void
    {
        $sites = $origin->siblingsSharingUser(includeSelf: true)
            ->get()
            ->filter(function (Site $site): bool {
                $type = $site->type();

                return $type instanceof AbstractSiteType && $type::supportsTooling();
            });

        if ($sites->isEmpty()) {
            return;
        }

        $workers = Worker::query()
            ->whereIn('site_id', $sites->pluck('id')->all())
            ->with('site')
            ->get();

        if ($workers->isEmpty()) {
            return;
        }

        $service = $origin->server->processManager();
        if (! $service instanceof Service) {
            return;
        }

        /** @var ProcessManager $processManager */
        $processManager = $service->handler();

        foreach ($workers as $worker) {
            try {
                $processManager->writeConfig($worker);

                if (ToolingRegistry::commandReferences($worker->command, $changedToolId)) {
                    $processManager->restart($worker->id, $worker->site_id);
                }
            } catch (Throwable $e) {
                ServerLog::log(
                    $origin->server,
                    "site-tooling-worker-refresh-failed-{$worker->id}",
                    $e->getMessage(),
                    $worker->site,
                );
            }
        }
    }
}
