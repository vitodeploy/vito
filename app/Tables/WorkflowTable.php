<?php

namespace App\Tables;

class WorkflowTable extends AbstractTable
{
    protected string $pageName = 'workflowsPage';

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
            Column::make('updated_at', 'Updated at')
                ->sortable()
                ->date(),
            Column::make('id', '')
                ->hidden(),
            Column::make('project_id', '')
                ->hidden(),
        ];
    }
}
