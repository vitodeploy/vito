<?php

namespace App\Actions\Monitoring;

use App\Models\Metric;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use stdClass;

class GetMetrics
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{current: ?array<string, mixed>, history: Collection<int, stdClass>}
     */
    public function filter(Server $server, array $input): array
    {
        $input = array_merge(['period' => '10m'], $input);

        $this->validate($input);

        if (isset($input['from'])) {
            $input['from'] = Carbon::parse($input['from'])->format('Y-m-d').' 00:00:00';
        }

        if (isset($input['to'])) {
            $input['to'] = Carbon::parse($input['to'])->format('Y-m-d').' 23:59:59';
        }

        return [
            'current' => $this->current($server),
            'history' => $this->metrics(
                server: $server,
                fromDate: $this->getFromDate($input),
                toDate: $this->getToDate($input),
                interval: $this->getInterval($input)
            ),
        ];
    }

    /**
     * @return ?array<string, mixed>
     */
    private function current(Server $server): ?array
    {
        /** @var ?Metric $latest */
        $latest = $server->metrics()->latest('id')->first();

        if (! $latest) {
            return null;
        }

        $diskUsedPercent = $latest->disk_total > 0
            ? round(($latest->disk_used / $latest->disk_total) * 100, 2)
            : null;

        $memoryUsedPercent = $latest->memory_total > 0
            ? round(($latest->memory_used / $latest->memory_total) * 100, 2)
            : null;

        return [
            'date' => $latest->created_at->format('Y-m-d H:i:s'),
            'cpu_cores' => $latest->cpu_cores,
            'cpu_physical_cores' => $latest->cpu_physical_cores,
            'cpu_usage_percent' => $latest->cpu_usage_percent,
            'memory_used_percent' => $memoryUsedPercent,
            'swap_used_percent' => $latest->swap_used_percent,
            'disk_used_percent' => $diskUsedPercent,
            'uptime_seconds' => $latest->uptime_seconds,
            'reboot_required' => $latest->reboot_required,
        ];
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function metrics(
        Server $server,
        Carbon $fromDate,
        Carbon $toDate,
        ?Expression $interval = null
    ): Collection {
        return DB::table('metrics')
            ->where('server_id', $server->id)
            ->whereBetween('created_at', [$fromDate->format('Y-m-d H:i:s'), $toDate->format('Y-m-d H:i:s')])
            ->select(
                [
                    DB::raw('created_at as date'),
                    DB::raw('ROUND(AVG(load), 2) as load'),
                    DB::raw('ROUND(AVG(memory_total), 2) as memory_total'),
                    DB::raw('ROUND(AVG(memory_used), 2) as memory_used'),
                    DB::raw('ROUND(AVG(memory_free), 2) as memory_free'),
                    DB::raw('ROUND(AVG(disk_total), 2) as disk_total'),
                    DB::raw('ROUND(AVG(disk_used), 2) as disk_used'),
                    DB::raw('ROUND(AVG(disk_free), 2) as disk_free'),
                    DB::raw('ROUND(AVG(cpu_usage_percent), 2) as cpu_usage_percent'),
                    DB::raw('ROUND(AVG(cpu_steal_percent), 2) as cpu_steal_percent'),
                    DB::raw('ROUND(AVG(swap_total), 0) as swap_total'),
                    DB::raw('ROUND(AVG(swap_used), 0) as swap_used'),
                    DB::raw('ROUND(AVG(swap_free), 0) as swap_free'),
                    DB::raw('ROUND(AVG(swap_used_percent), 2) as swap_used_percent'),
                    DB::raw('MAX(oom_kill_count) as oom_kill_count'),
                    $interval,
                ],
            )
            ->groupByRaw('date_interval')
            ->orderBy('date_interval')
            ->get()
            ->map(function ($item): stdClass {
                $floatFields = [
                    'load', 'memory_total', 'memory_used', 'memory_free',
                    'disk_total', 'disk_used', 'disk_free',
                    'cpu_usage_percent', 'cpu_steal_percent',
                    'swap_total', 'swap_used', 'swap_free', 'swap_used_percent',
                ];
                foreach ($floatFields as $key) {
                    $item->{$key} = $item->{$key} !== null ? (float) $item->{$key} : null;
                }
                $item->oom_kill_count = $item->oom_kill_count !== null ? (int) $item->oom_kill_count : null;
                $item->date = Carbon::parse($item->date)->format('Y-m-d H:i');
                $item->disk_used_percent = ($item->disk_total ?? 0) > 0
                    ? round(($item->disk_used / $item->disk_total) * 100, 2)
                    : null;
                $item->memory_used_percent = ($item->memory_total ?? 0) > 0
                    ? round(($item->memory_used / $item->memory_total) * 100, 2)
                    : null;
                unset($item->date_interval);

                return $item;
            });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function getFromDate(array $input): Carbon
    {
        if ($input['period'] === 'custom') {
            return new Carbon($input['from']);
        }

        return Carbon::parse('-'.convert_time_format($input['period']));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function getToDate(array $input): Carbon
    {
        if ($input['period'] === 'custom') {
            return new Carbon($input['to']);
        }

        return Carbon::now();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function getInterval(array $input): Expression
    {
        if ($input['period'] === 'custom') {
            $from = new Carbon($input['from']);
            $to = new Carbon($input['to']);
            $periodInHours = $from->diffInHours($to);
        }

        if (! isset($periodInHours)) {
            $periodInHours = Carbon::parse(
                convert_time_format($input['period'])
            )->diffInHours();
        }

        if (abs($periodInHours) <= 1) {
            return DB::raw("strftime('%Y-%m-%d %H:%M:00', created_at) as date_interval");
        }

        if ($periodInHours <= 24) {
            return DB::raw("strftime('%Y-%m-%d %H:00:00', created_at) as date_interval");
        }

        return DB::raw("strftime('%Y-%m-%d 00:00:00', created_at) as date_interval");
    }

    private function validate(array $input): void
    {
        $isCustom = ($input['period'] ?? null) === 'custom';

        $rules = [
            'period' => [
                'required',
                Rule::in([
                    '10m',
                    '30m',
                    '1h',
                    '12h',
                    '1d',
                    '7d',
                    'custom',
                ]),
            ],
            'from' => array_filter([$isCustom ? 'required' : 'nullable', 'date', $isCustom ? 'before_or_equal:to' : null]),
            'to' => array_filter([$isCustom ? 'required' : 'nullable', 'date', $isCustom ? 'after_or_equal:from' : null]),
        ];

        Validator::make($input, $rules)->validate();
    }
}
