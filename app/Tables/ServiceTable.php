<?php

namespace App\Tables;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\SupportsNetworking;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\ComponentColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ServiceTable extends Table
{
    protected array $tableSettings = ['realtime' => 'service'];

    protected string $defaultSort = 'id';

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('log');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('version', 'Version')
                ->value(fn (Service $service): string => $service->installed_version ?: $service->version)
                ->accessor('installed_version')
                ->sortable(),
            DateTimeColumn::make('created_at', 'Installed at')->sortable(),
            EnumColumn::make('status', 'Status')->sortable(),
            ComponentColumn::create('networked', 'Networked', 'ServiceNetworkedBadge')
                ->value(function (Service $service): string {
                    $handler = $service->hasHandler() ? $service->handler() : null;

                    if (! $handler instanceof SupportsNetworking) {
                        return 'n/a';
                    }

                    return match ($service->type_data['networking_effective'] ?? null) {
                        true => 'yes',
                        false => 'no',
                        default => 'unknown',
                    };
                }),
            Column::data('id'),
            Column::data('resource', fn (Service $service) => ServiceResource::make($service)),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name'];
    }
}
