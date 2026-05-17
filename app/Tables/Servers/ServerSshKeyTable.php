<?php

namespace App\Tables\Servers;

use App\Models\SshKey;
use Forjed\InertiaTable\Column;
use Forjed\InertiaTable\Columns\ActionsColumn;
use Forjed\InertiaTable\Columns\DateTimeColumn;
use Forjed\InertiaTable\Columns\TextColumn;
use Forjed\InertiaTable\Table;

class ServerSshKeyTable extends Table
{
    protected function query(): void
    {
        $this->perPage = config('web.pagination_size');
        $this->query->with('user')->latest();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name', 'Name')->sortable(),
            TextColumn::make('owner', 'Owner')
                ->value(fn (SshKey $k) => $k->user->name)
                ->fallback('-'),
            TextColumn::make('deployment_user', 'Deployed to User')
                ->value(fn (SshKey $k) => $k->pivot->user ?? null)
                ->fallback('-'),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            Column::data('id'),
            Column::data('owner_email', fn (SshKey $k) => $k->user->email),
            ActionsColumn::make(),
        ];
    }
}
