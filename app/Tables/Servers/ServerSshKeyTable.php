<?php

namespace App\Tables\Servers;

use App\Models\SshKey;
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
            TextColumn::make('user', 'Owner')
                ->value(fn (SshKey $k) => $k->user ? "{$k->user->name} ({$k->user->email})" : null)
                ->fallback('-'),
            TextColumn::make('deployment_user', 'Deployed to User')->fallback('-')->sortable(),
            DateTimeColumn::make('created_at', 'Created at')->sortable()->toLocal(),
            ActionsColumn::make(),
        ];
    }
}
