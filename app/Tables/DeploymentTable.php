<?php

namespace App\Tables;

use App\Http\Resources\ServerLogResource;
use App\Models\Deployment;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\EnumColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class DeploymentTable extends Table
{
    protected array $tableSettings = ['realtime' => 'deployment'];

    protected string $defaultSort = '-created_at';

    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('log', 'site');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('id', 'ID')->sortable(),
            Column::make('commit', 'Commit'),
            DateTimeColumn::make('created_at', 'Deployed At')->sortable()->toLocal(),
            EnumColumn::make('status', 'Status')->sortable(),
            Column::make('release', 'Release'),
            Column::data('site_id'),
            Column::data('server_id', fn (Deployment $deployment) => $deployment->site->server_id),
            Column::data('active'),
            Column::data('commit_data'),
            Column::data('log', fn (Deployment $deployment) => $deployment->log ? ServerLogResource::make($deployment->log) : null),
            ActionsColumn::make(),
        ];
    }
}
