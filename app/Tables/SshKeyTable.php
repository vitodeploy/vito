<?php

namespace App\Tables;

class SshKeyTable extends AbstractTable
{
    protected string $pageName = 'sshKeysPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('id', '')
                ->hidden(),
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
