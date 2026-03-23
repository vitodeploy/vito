<?php

namespace App\Tables;

use App\Models\Command;

class CommandTable extends AbstractTable
{
    protected string $pageName = 'commandsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('command', 'Command')
                ->sortable()
                ->copyable(),
            Column::make('server_id', '')
                ->hidden()
                ->value(fn (Command $command) => $command->site->server_id),
            Column::make('site_id', '')
                ->hidden(),
            Column::make('id', '')
                ->hidden(),
            Column::make('variables', '')
                ->hidden()
                ->value(fn (Command $command) => $command->getVariables()),
        ];
    }
}
