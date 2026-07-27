<?php

namespace App\Actions\Service;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Exceptions\SSHError;
use App\Http\Resources\ServiceResource;
use App\Models\Server;
use App\Models\Service;
use App\Services\AbstractService;
use App\Services\SupportsNetworking;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProbeServices
{
    /**
     * @throws SSHError
     */
    public function probe(Server $server): void
    {
        $descriptors = $this->descriptors($server);

        if ($descriptors === []) {
            $this->broadcastRefreshed($server);

            return;
        }

        $output = $server->ssh()->exec(
            view('ssh.os.refresh-services', $this->viewData($descriptors)),
            'refresh-services',
            timeout: static::budget($server)
        );

        $sections = $this->parseSections($output);

        foreach ($descriptors as $descriptor) {
            $this->persist($server, $descriptor, $sections[$descriptor['service']->id] ?? []);
        }

        $this->broadcastRefreshed($server);
    }

    public static function budget(Server $server): int
    {
        $networked = 0;
        $other = 0;

        foreach ($server->services()->whereIn('status', SyncServiceStatus::SETTLED_STATUSES)->get() as $service) {
            if (! $service->hasHandler()) {
                continue;
            }

            if ($service->handler() instanceof SupportsNetworking) {
                $networked++;

                continue;
            }

            $other++;
        }

        $budget = 40 + (30 * $networked) + (15 * $other);
        $ceiling = ((int) config('horizon.defaults.ssh.timeout', 1200)) - 60;

        return max(60, min($budget, $ceiling));
    }

    /**
     * @return array<int, array{service: Service, handler: object, unit: string, versionCommand: ?string, networkingCommand: ?string, networkingRequiresRunning: bool}>
     */
    private function descriptors(Server $server): array
    {
        $descriptors = [];

        $services = $server->services()
            ->whereIn('status', SyncServiceStatus::SETTLED_STATUSES)
            ->get();

        foreach ($services as $service) {
            if (! $service->hasHandler()) {
                continue;
            }

            $handler = $service->handler();
            $unit = $handler->unit();
            $versionCommand = $handler instanceof AbstractService ? $handler->versionCommand() : null;
            $networkingCommand = null;
            $networkingRequiresRunning = false;

            if ($handler instanceof SupportsNetworking) {
                $networkingRequiresRunning = $handler->networkingProbeRequiresRunning();

                if (! $networkingRequiresRunning || $unit !== '') {
                    $networkingCommand = $handler->networkingProbeCommand();
                }
            }

            if ($unit === '' && $versionCommand === null && $networkingCommand === null) {
                continue;
            }

            $descriptors[] = [
                'service' => $service,
                'handler' => $handler,
                'unit' => $unit,
                'versionCommand' => $versionCommand,
                'networkingCommand' => $networkingCommand,
                'networkingRequiresRunning' => $networkingRequiresRunning,
            ];
        }

        return $descriptors;
    }

    /**
     * @param  array<int, array<string, mixed>>  $descriptors
     * @return array<string, mixed>
     */
    private function viewData(array $descriptors): array
    {
        $units = [];
        $services = [];

        foreach ($descriptors as $descriptor) {
            /** @var Service $service */
            $service = $descriptor['service'];
            $unit = (string) $descriptor['unit'];
            $stateIndex = null;

            if ($unit !== '') {
                $units[] = escapeshellarg($unit);
                $stateIndex = count($units);
            }

            $services[] = [
                'id' => $service->id,
                'stateIndex' => $stateIndex,
                'versionFragment' => $descriptor['versionCommand'] === null
                    ? null
                    : escapeshellarg((string) $descriptor['versionCommand']),
                'networkingFragment' => $descriptor['networkingCommand'] === null
                    ? null
                    : escapeshellarg((string) $descriptor['networkingCommand']),
                'networkingRequiresRunning' => (bool) $descriptor['networkingRequiresRunning'],
            ];
        }

        return [
            'unitsArgument' => escapeshellarg('sudo systemctl is-active '.implode(' ', $units)),
            'unitCount' => count($units),
            'services' => $services,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseSections(string $output): array
    {
        $parts = preg_split(
            '/^###VITO:(\d+):(\w+)###\s*$/m',
            $output,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        if ($parts === false) {
            return [];
        }

        $sections = [];

        for ($i = 1; $i + 2 <= count($parts); $i += 3) {
            $id = (int) $parts[$i];
            $section = $parts[$i + 1];
            $body = trim($parts[$i + 2] ?? '');

            if (isset($sections[$id][$section])) {
                continue;
            }

            $sections[$id][$section] = $body;
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $descriptor
     * @param  array<string, string>  $sections
     */
    private function persist(Server $server, array $descriptor, array $sections): void
    {
        if ($sections === []) {
            return;
        }

        /** @var Service $service */
        $service = $descriptor['service'];
        $handler = $descriptor['handler'];

        try {
            $service->refresh();
        } catch (ModelNotFoundException) {
            return;
        }

        $changed = false;

        if (array_key_exists('version', $sections) && $handler instanceof AbstractService) {
            $version = $handler->parseVersionOutput($sections['version']);

            if ($version !== null && $version !== $service->installed_version) {
                $service->installed_version = $version;
                $changed = true;
            }
        }

        if (array_key_exists('networking', $sections) && $handler instanceof SupportsNetworking) {
            $handler->rememberEffectiveNetworking($handler->parseNetworkingProbe($sections['networking']));
            $changed = true;
        }

        if ($changed) {
            $service->save();
        }

        $broadcast = false;

        if (array_key_exists('status', $sections)) {
            $broadcast = app(SyncServiceStatus::class)->sync($server, $service, $sections['status'], force: true);
        }

        $fresh = $service->fresh();

        if ($changed && ! $broadcast && $fresh instanceof Service) {
            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $server->project_id,
                type: 'service.updated',
                data: new ServiceResource($fresh),
            ));
        }
    }

    private function broadcastRefreshed(Server $server): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'service.refreshed',
            data: ['server_id' => $server->id],
        ));
    }
}
