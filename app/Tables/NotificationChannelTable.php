<?php

namespace App\Tables;

class NotificationChannelTable extends AbstractTable
{
    protected string $pageName = 'notificationChannelsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('provider', 'Provider')
                ->sortable(),
            Column::make('name', 'Name')
                ->value(fn ($m) => $m->label)
                ->sortable()
                ->accessor('label'),
            Column::make('global', 'Scope')
                ->value(fn ($m) => is_null($m->project_id) ? 'Global' : 'Project')
                ->badge(variant: 'outline'),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::data('id'),
            Column::data('project_id'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['label'];
    }
}
