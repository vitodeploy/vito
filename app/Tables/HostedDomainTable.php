<?php

namespace App\Tables;

use App\Enums\HostedDomainType;
use App\Http\Resources\SslResource;
use App\Models\HostedDomain;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\BadgeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class HostedDomainTable extends Table
{
    protected array $tableSettings = ['realtime' => 'hosted-domain'];

    protected string $defaultSort = 'created_at';

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query
            ->with('site', 'ssl')
            ->orderByRaw("CASE WHEN type = 'primary' THEN 0 ELSE 1 END");
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('domain', 'Domain')
                ->sortable()
                ->withIcon(fn (HostedDomain $hd) => match ($hd->type) {
                    HostedDomainType::PRIMARY => 'crown',
                    HostedDomainType::ALIAS => 'copy',
                    HostedDomainType::REDIRECT => 'signpost',
                }),
            EnumColumn::make('ssl_method', 'SSL')->uppercase(),
            Column::make('certificate', 'Certificate'),
            BadgeColumn::make('ssl.expires_at', 'Expires In')
                ->variant('outline')
                ->adjust(fn ($data) => $this->daysUntil($data)),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::data('id'),
            Column::data('site_id'),
            Column::data('server_id', fn (HostedDomain $hd) => $hd->site->server_id),
            Column::data('type', fn (HostedDomain $hd) => $hd->type->getText()),
            Column::data('type_color', fn (HostedDomain $hd) => $hd->type->getColor()),
            Column::data('ssl_id'),
            Column::data('error'),
            Column::data('ssl', fn (HostedDomain $hd) => $hd->ssl ? SslResource::make($hd->ssl) : null),
            ActionsColumn::make(),
        ];
    }

    private function daysUntil(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        $days = (int) now()->diffInDays($date, false);

        if ($days < 0) {
            return 'Expired';
        }

        return $days.' '.($days === 1 ? 'day' : 'days');
    }
}
