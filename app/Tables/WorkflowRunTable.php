<?php

namespace App\Tables;

class WorkflowRunTable extends AbstractTable
{
    protected string $pageName = 'workflowRunsPage';

    protected ?string $realtimeEvent = 'workflow-run';

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
            Column::data('workflow_id'),
        ];
    }
}
