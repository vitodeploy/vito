<?php

namespace App\Actions\SiteStats;

use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\Models\Site;
use App\Services\LogAnalysis\GoAccess\GoAccess;
use App\Support\Testing\SSHFake;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GetSiteStats
{
    /**
     * @return array{months: array<int, string>, month: string, summary: array<int, array<string, mixed>>, detail: ?array<string, mixed>}
     *
     * @throws SSHError
     */
    public function get(Site $site, ?string $month = null): array
    {
        $ssh = $site->server->ssh();

        $status = Cache::remember(
            "site-stats-status:{$site->id}",
            10,
            fn (): ?array => $this->readJson($ssh, $site, 'status.json'),
        );
        $token = (string) ($status['last_run_finished_at'] ?? 'none');

        return Cache::remember(
            "site-stats:{$site->id}:".($month ?? 'current').":{$token}",
            300,
            function () use ($ssh, $site, $month, $status): array {
                $summary = $this->readJson($ssh, $site, 'summary.json') ?? [];
                $summary = collect($summary)
                    ->filter(fn ($row): bool => is_array($row) && isset($row['month']))
                    ->sortBy('month')
                    ->values()
                    ->all();

                $months = collect($summary)->pluck('month')->sortDesc()->values()->all();
                $selected = $month && in_array($month, $months, true)
                    ? $month
                    : ($months[0] ?? Carbon::now()->format('Y-m'));

                return [
                    'months' => $months,
                    'month' => $selected,
                    'summary' => $summary,
                    'detail' => $this->detail($ssh, $site, $selected, $status),
                    'status' => $status ? [
                        'last_success_at' => $status['last_success_at'] ?? null,
                        'last_run_finished_at' => $status['last_run_finished_at'] ?? null,
                        'exit_code' => $status['exit_code'] ?? null,
                        'error' => $this->sanitizeError($status['error'] ?? null),
                    ] : null,
                ];
            }
        );
    }

    private function sanitizeError(mixed $error): ?string
    {
        if (! is_string($error) || $error === '') {
            return null;
        }

        $clean = trim((string) preg_replace('/[[:cntrl:]]+/', ' ', $error));

        return $clean === '' ? null : mb_substr($clean, 0, 200);
    }

    /**
     * @param  ?array<string, mixed>  $status
     * @return ?array<string, mixed>
     *
     * @throws SSHError
     */
    private function detail(SSH|SSHFake $ssh, Site $site, string $month, ?array $status): ?array
    {
        $report = $this->readMonth($ssh, $site, $month);
        if ($report === null) {
            return null;
        }

        return [
            'generated_at' => $status['last_success_at'] ?? null,
            'totals' => [
                'visitors' => (int) ($report['general']['unique_visitors'] ?? 0),
                'hits' => (int) ($report['general']['total_requests'] ?? 0),
                'bandwidth' => (int) ($report['general']['bandwidth'] ?? 0),
            ],
            'daily' => $this->daily($report, $month),
            'top_pages' => $this->panel($report, 'requests', 12),
            'referrers' => $this->panel($report, 'referrers', 20),
            'status_codes' => $this->statusCodes($report),
            'not_found' => $this->panel($report, 'not_found', 20),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, mixed>>
     */
    private function daily(array $report, string $month): array
    {
        $byDate = [];
        foreach ($report['visitors']['data'] ?? [] as $row) {
            $byDate[$this->formatDate((string) ($row['data'] ?? ''))] = [
                'visitors' => (int) ($row['visitors']['count'] ?? 0),
                'hits' => (int) ($row['hits']['count'] ?? 0),
                'bandwidth' => (int) ($row['bytes']['count'] ?? 0),
            ];
        }

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return [];
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $days = [];
        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $date = $start->copy()->day($day)->format('Y-m-d');
            $days[] = array_merge(
                ['date' => $date],
                $byDate[$date] ?? ['visitors' => 0, 'hits' => 0, 'bandwidth' => 0]
            );
        }

        return $days;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, mixed>>
     */
    private function panel(array $report, string $key, int $limit): array
    {
        return collect($report[$key]['data'] ?? [])
            ->take($limit)
            ->map(fn (array $row): array => [
                'name' => (string) ($row['data'] ?? ''),
                'hits' => (int) ($row['hits']['count'] ?? 0),
                'visitors' => (int) ($row['visitors']['count'] ?? 0),
                'bandwidth' => (int) ($row['bytes']['count'] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, mixed>>
     */
    private function statusCodes(array $report): array
    {
        $codes = [];
        foreach ($report['status_codes']['data'] ?? [] as $row) {
            $children = $row['items'] ?? [$row];
            foreach ($children as $child) {
                $codes[] = [
                    'name' => (string) ($child['data'] ?? ''),
                    'hits' => (int) ($child['hits']['count'] ?? 0),
                ];
            }
        }

        return $codes;
    }

    /**
     * @return ?array<string, mixed>
     *
     * @throws SSHError
     */
    private function readJson(SSH|SSHFake $ssh, Site $site, string $file): ?array
    {
        return $this->cat($ssh, GoAccess::BASE_DIR."/data/{$site->id}/{$file}");
    }

    /**
     * @return ?array<string, mixed>
     *
     * @throws SSHError
     */
    private function readMonth(SSH|SSHFake $ssh, Site $site, string $month): ?array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return null;
        }

        $report = $this->cat($ssh, GoAccess::BASE_DIR."/data/{$site->id}/{$month}/report.json");

        return ($report !== null && isset($report['general'])) ? $report : null;
    }

    /**
     * @return ?array<string, mixed>
     *
     * @throws SSHError
     */
    private function cat(SSH|SSHFake $ssh, string $path): ?array
    {
        $out = trim($ssh->exec('cat '.escapeshellarg($path).' 2>/dev/null || echo ""'));
        if ($out === '') {
            return null;
        }

        $decoded = json_decode($out, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        return is_array($decoded) ? $decoded : null;
    }

    private function formatDate(string $value): string
    {
        if (preg_match('/^\d{8}$/', $value) === 1) {
            return Carbon::createFromFormat('Ymd', $value)->format('Y-m-d');
        }

        return $value;
    }
}
