<?php

namespace App\Tables;

use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Models\Server;
use App\Models\Site;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BadgeColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class SiteTable extends Table
{
    protected array $tableSettings = ['realtime' => 'site'];

    protected ?Server $server = null;

    public function forServer(Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with(['server', 'hostedDomains.ssl'])->latest();
    }

    protected function columns(): array
    {
        $columns = [];

        if (! $this->server) {
            $columns[] = Column::make('server.name', 'Server')
                ->link('servers.show', ['server' => ':server_id']);
        }

        return [
            ...$columns,
            Column::make('id', 'ID')->sortable(),
            TextColumn::make('domain', 'Domain')->sortable(),
            BadgeColumn::make('type', 'Type')->variant('outline')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable(),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('server_id'),
            Column::data('warnings', fn (Site $site) => $this->getWarnings($site)),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['domain'];
    }

    /**
     * @return array<int, array{key: string, ...}>
     */
    private function getWarnings(Site $site): array
    {
        $warnings = [];

        $hostedDomains = $site->relationLoaded('hostedDomains') ? $site->hostedDomains : collect();

        $pendingDomains = $hostedDomains->where('status', HostedDomainStatus::PENDING);
        if ($pendingDomains->isNotEmpty()) {
            $warnings[] = [
                'key' => 'pending_domains',
                'count' => $pendingDomains->count(),
                'domains' => $pendingDomains->pluck('domain')->all(),
            ];
        }

        if (! $site->ssl_enabled) {
            $warnings[] = ['key' => 'ssl_disabled'];
        }

        if (! $site->vhost_generation_enabled) {
            $warnings[] = ['key' => 'vhost_generation_disabled'];
        }

        $expiring = $hostedDomains->filter(
            fn ($hd) => $hd->ssl_id
                && $hd->relationLoaded('ssl')
                && $hd->ssl
                && $hd->ssl->status === SslStatus::CREATED
                && $hd->ssl->expires_at
                && $hd->ssl->expires_at <= now()->addDays(14)
        );

        if ($expiring->isNotEmpty()) {
            $earliestExpiry = $expiring->min(fn ($hd) => $hd->ssl->expires_at);
            $warnings[] = [
                'key' => 'ssl_expiring',
                'count' => $expiring->count(),
                'domains' => $expiring->pluck('domain')->all(),
                'earliest_expiry' => $earliestExpiry?->toIso8601String(),
            ];
        }

        return $warnings;
    }
}
