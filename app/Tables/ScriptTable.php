<?php

namespace App\Tables;

use App\Models\Script;

class ScriptTable extends AbstractTable
{
    protected string $pageName = 'scriptsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('id', '')
                ->hidden(),
            Column::make('user_id', '')
                ->hidden(),
            Column::make('content', '')
                ->hidden(),
            Column::make('variables', '')
                ->hidden()
                ->value(fn (Script $script) => $script->getVariables()),
        ];
    }
}
