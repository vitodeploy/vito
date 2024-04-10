<?php

namespace App\Actions\Monitoring;

use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GetMetrics
{
    public function filter(Server $server, array $input): array
    {
        if (isset($input['from']) && isset($input['to']) && $input['from'] === $input['to']) {
            $input['from'] = Carbon::parse($input['from'])->format('Y-m-d').' 00:00:00';
            $input['to'] = Carbon::parse($input['to'])->format('Y-m-d').' 23:59:59';
        }

        $defaultInput = [
            'period' => '10m',
        ];

        $input = array_merge($defaultInput, $input);

        $this->validate($input);

        return $this->metrics(
            server: $server,
            fromDate: $this->getFromDate($input),
            toDate: $this->getToDate($input),
            interval: $this->getInterval($input)
        );
    }

    private function metrics(
        Server $server,
        Carbon $fromDate,
        Carbon $toDate,
        ?string $interval = null
    ): array {
        $metrics = DB::table('metrics')
            ->where('server_id', $server->id)
            ->whereBetween('created_at', [$fromDate->format('Y-m-d H:i:s'), $toDate->format('Y-m-d H:i:s')])
            ->select(
                [
                    DB::raw('created_at as date'),
                    DB::raw('load as load'),
                    DB::raw('memory_total as memory_total'),
                    DB::raw('memory_used as memory_used'),
                    DB::raw('memory_free as memory_free'),
                    DB::raw('disk_total as disk_total'),
                    DB::raw('disk_used as disk_used'),
                    DB::raw('disk_free as disk_free'),
                    DB::raw('datetime(created_at, \'-1 '.$interval.'\') as date_interval'),
                ],
            )
            ->groupByRaw('date_interval')
            ->orderBy('date_interval')
            ->get()
            ->map(function ($item) {
                $item->date = Carbon::parse($item->date)->format('Y-m-d H:i');

                return $item;
            });

        return [
            'metrics' => $metrics,
        ];
    }

    private function getFromDate(array $input): Carbon
    {
        if ($input['period'] === 'custom') {
            return new Carbon($input['from']);
        }

        return Carbon::parse('-'.convert_time_format($input['period']));
    }

    private function getToDate(array $input): Carbon
    {
        if ($input['period'] === 'custom') {
            return new Carbon($input['to']);
        }

        return Carbon::now();
    }

    private function getInterval(array $input): string
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

        if ($periodInHours <= 1) {
            return 'minute';
        }

        if ($periodInHours <= 24) {
            return 'hour';
        }

        if ($periodInHours > 24) {
            return 'day';
        }

        return 'minute';
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'period' => [
                'required',
                Rule::in([
                    '10m',
                    '30m',
                    '1h',
                    '12h',
                    '1d',
                    '7d',
                    '30d',
                    'custom',
                ]),
            ],
        ])->validate();

        if ($input['period'] === 'custom') {
            Validator::make($input, [
                'from' => [
                    'required',
                    'date',
                    'before:to',
                ],
                'to' => [
                    'required',
                    'date',
                    'after:from',
                ],
            ])->validate();
        }
    }
}
