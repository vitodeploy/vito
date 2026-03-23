<?php

namespace App\Tables;

use App\Models\Project;

class ProjectTable extends AbstractTable
{
    protected string $pageName = 'projectsPage';

    protected int $perPage = 20;

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('role', 'Role')
                ->value(fn (Project $m) => $m->role(user())->value)
                ->badge(variant: 'outline'),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::data('id'),
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
