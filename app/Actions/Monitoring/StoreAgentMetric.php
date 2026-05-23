<?php

namespace App\Actions\Monitoring;

use App\Models\Metric;
use App\Models\Server;
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
        ])->validate();

        /** @var Metric $metric */
        $metric = $server->metrics()->create(array_merge($validated, ['server_id' => $server->id]));

        return $metric;
    }
}
