<?php

namespace App\Actions\Monitoring;

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
     * @return Collection<int, mixed>
     */
    public function filter(Server $server, array $input): Collection
    {
        Validator::make($input, self::rules($input))->validate();

        if (isset($input['from'])) {
            $input['from'] = Carbon::parse($input['from'])->format('Y-m-d').' 00:00:00';
        }

        if (isset($input['to'])) {
            $input['to'] = Carbon::parse($input['to'])->format('Y-m-d').' 23:59:59';
        }

        $defaultInput = [
            'period' => '10m',
        ];

        $input = array_merge($defaultInput, $input);

        return $this->metrics(
            server: $server,
            fromDate: $this->getFromDate($input),
            toDate: $this->getToDate($input),
            interval: $this->getInterval($input)
        );
    }

    /**
     * @return Collection<int, mixed>
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
                    $interval,
                ],
            )
            ->groupByRaw('date_interval')
            ->orderBy('date_interval')
            ->get()
            ->map(function ($item): stdClass {
                $item->date = Carbon::parse($item->date)->format('Y-m-d H:i');

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

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     */
    public static function rules(array $input): array
    {
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
        ];

        if (isset($input['period']) && $input['period'] === 'custom') {
            $rules['from'] = ['required', 'date', 'before_or_equal:to'];
            $rules['to'] = ['required', 'date', 'after_or_equal:from'];
        }

        return $rules;
    }
}
