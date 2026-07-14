<?php

namespace App\Actions\Monitoring;

use App\Actions\Service\SyncServiceStatus;
use App\Models\Metric;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class StoreAgentMetric
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function store(Server $server, array $input): Metric
    {
        $validated = Validator::make($input, [
            'load' => 'required|numeric',
            'memory_total' => 'required|numeric',
            'memory_used' => 'required|numeric',
            'memory_free' => 'required|numeric',
            'disk_total' => 'required|numeric',
            'disk_used' => 'required|numeric',
            'disk_free' => 'required|numeric',
            'cpu_cores' => 'nullable|integer',
            'cpu_physical_cores' => 'nullable|integer',
            'cpu_usage_percent' => 'nullable|numeric',
            'cpu_per_core_usage_percent' => 'nullable|array|max:256',
            'cpu_per_core_usage_percent.*' => 'numeric',
            'cpu_steal_percent' => 'nullable|numeric',
            'swap_total' => 'nullable|numeric',
            'swap_used' => 'nullable|numeric',
            'swap_free' => 'nullable|numeric',
            'swap_used_percent' => 'nullable|numeric',
            'oom_kill_count' => 'nullable|integer',
            'uptime_seconds' => 'nullable|numeric',
            'reboot_required' => 'nullable|boolean',
            'services' => 'nullable|array|max:100',
            'services.*.id' => 'required|integer',
            'services.*.status' => 'required|string|max:32',
        ])->validate();

        /** @var Metric $metric */
        $metric = $server->metrics()->create(array_merge(Arr::except($validated, ['services']), ['server_id' => $server->id]));

        if (! empty($validated['services'])) {
            $this->syncServiceStatuses($server, $validated['services']);
        }

        return $metric;
    }

    /**
     * @param  array<int, array{id: int, status: string}>  $services
     */
    private function syncServiceStatuses(Server $server, array $services): void
    {
        $entries = [];
        foreach ($services as $entry) {
            $entries[(int) $entry['id']] = $entry;
        }

        $serverServices = $server->services()
            ->whereIn('id', array_keys($entries))
            ->whereIn('status', SyncServiceStatus::SETTLED_STATUSES)
            ->where('type', '!=', 'monitoring')
            ->get()
            ->keyBy('id');

        foreach ($entries as $id => $entry) {
            /** @var ?Service $service */
            $service = $serverServices->get($id);
            if (! $service || ! $service->hasHandler() || ! $service->handler()->canBeManaged()) {
                continue;
            }
            app(SyncServiceStatus::class)->sync($server, $service, $entry['status']);
        }
    }
}
