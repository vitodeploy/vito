<?php

namespace App\Tables;

class ServerSshKeyTable extends AbstractTable
{
    protected string $pageName = 'serverSshKeysPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('user_info', 'Owner')
                ->value(fn ($m) => $m->user ? $m->user->name.' ('.$m->user->email.')' : '-'),
            Column::make('deployment_user', 'Deployed to User')
                ->value(fn ($m) => $m->pivot->user ?? '-'),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::data('id'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name'];
    }
}
