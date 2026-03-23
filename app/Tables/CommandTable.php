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
            Column::data('server_id', fn (Command $command) => $command->site->server_id),
            Column::data('site_id'),
            Column::data('id'),
            Column::data('variables', fn (Command $command) => $command->getVariables()),
        ];
    }
}
