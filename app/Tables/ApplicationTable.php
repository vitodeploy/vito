<?php

namespace App\Tables;

use App\Http\Resources\EnvironmentSummaryResource;
use App\Models\Application;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ApplicationTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->withCount('environments')->with(['environments' => fn ($query) => $query->orderBy('id')])->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('repository', 'Repository'),
            TextColumn::make('runtime', 'Runtime')
                ->value(fn (Application $application): string => config('component.runtimes', [])[$application->runtime]['label'] ?? $application->runtime),
            TextColumn::make('environments_count', 'Environments'),
            EnumColumn::make('status', 'Status'),
            Column::data('id'),
            Column::data('environments', fn (Application $application): array => EnvironmentSummaryResource::collection($application->environments)->resolve()),
            ActionsColumn::make(),
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'repository'];
    }
}
