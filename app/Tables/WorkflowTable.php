<?php

namespace App\Tables;

use App\Models\Workflow;

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
            Column::data('id'),
            Column::data('project_id'),
            /** @phpstan-ignore nullsafe.neverNull */
            Column::data('run_inputs', fn (Workflow $workflow) => $workflow->getStartingNode()?->inputs ?? []),
        ];
    }
}
