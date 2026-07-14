<?php

namespace App\Listeners;

use App\Actions\Script\ExecuteScript;
use App\Enums\ScriptEventHookEvent;
use App\Events\ServerDeletedEvent;
use App\Events\ServerInstalledEvent;
use App\Events\ServiceInstalledEvent;
use App\Events\ServiceUninstalledEvent;
use App\Events\SiteCreatedEvent;
use App\Events\SiteDeletedEvent;
use App\Models\ScriptEventHook;
use App\Models\Server;
use Illuminate\Support\Facades\Log;

class RunScriptEventHooks
{
    public function handle(
        SiteCreatedEvent|SiteDeletedEvent|ServerInstalledEvent|ServerDeletedEvent|ServiceInstalledEvent|ServiceUninstalledEvent $event
    ): void {
        [$eventEnum, $projectId, $variables] = $this->extractContext($event);

        ScriptEventHook::query()
            ->where('project_id', $projectId)
            ->where('event', $eventEnum->value)
            ->where('enabled', true)
            ->with(['script', 'server'])
            ->get()
            ->each(function (ScriptEventHook $hook) use ($eventEnum, $variables): void {
                try {
                    app(ExecuteScript::class)->executeForHook($hook, $variables);
                } catch (\Throwable $e) {
                    Log::error('Script event hook failed', [
                        'hook_id' => $hook->id,
                        'event' => $eventEnum->value,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }

    /**
     * @return array{0: ScriptEventHookEvent, 1: int, 2: array<string, string>}
     */
    private function extractContext(
        SiteCreatedEvent|SiteDeletedEvent|ServerInstalledEvent|ServerDeletedEvent|ServiceInstalledEvent|ServiceUninstalledEvent $event
    ): array {
        if ($event instanceof SiteCreatedEvent) {
            return [
                ScriptEventHookEvent::SITE_CREATED,
                $event->site->server->project_id,
                [
                    'site_domain' => $event->site->domain,
                    'site_path' => $event->site->path,
                    'site_type' => $event->site->type,
                    'server_name' => $event->site->server->name,
                    'server_ip' => $event->site->server->ip,
                ],
            ];
        }

        if ($event instanceof SiteDeletedEvent) {
            return [
                ScriptEventHookEvent::SITE_DELETED,
                $event->server->project_id,
                [
                    'site_domain' => $event->domain,
                    'server_name' => $event->server->name,
                    'server_ip' => $event->server->ip,
                ],
            ];
        }

        if ($event instanceof ServerInstalledEvent) {
            return [
                ScriptEventHookEvent::SERVER_INSTALLED,
                $event->server->project_id,
                [
                    'server_name' => $event->server->name,
                    'server_ip' => $event->server->ip,
                ],
            ];
        }

        if ($event instanceof ServerDeletedEvent) {
            return [
                ScriptEventHookEvent::SERVER_DELETED,
                $event->projectId,
                [
                    'server_name' => $event->serverName,
                    'server_ip' => $event->serverIp,
                ],
            ];
        }

        if ($event instanceof ServiceInstalledEvent) {
            return [
                ScriptEventHookEvent::SERVICE_INSTALLED,
                $event->service->server->project_id,
                [
                    'service_name' => $event->service->name,
                    'service_type' => $event->service->type,
                    'service_version' => $event->service->installed_version ?? $event->service->version,
                    'server_name' => $event->service->server->name,
                    'server_ip' => $event->service->server->ip,
                ],
            ];
        }

        $server = Server::query()->find($event->serverId);

        return [
            ScriptEventHookEvent::SERVICE_UNINSTALLED,
            $event->projectId,
            [
                'service_name' => $event->serviceName,
                'service_type' => $event->serviceType,
                'server_name' => $server->name ?? '',
                'server_ip' => $server->ip ?? '',
            ],
        ];
    }
}
