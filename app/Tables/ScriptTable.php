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
            Column::data('id'),
            Column::data('user_id'),
            Column::data('content'),
            Column::data('variables', fn (Script $script) => $script->getVariables()),
        ];
    }
}
