<?php

namespace App\Tables;

class WorkflowRunTable extends AbstractTable
{
    protected string $pageName = 'workflowRunsPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')
                ->sortable(),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::make('workflow_id', '')
                ->hidden(),
        ];
    }
}
