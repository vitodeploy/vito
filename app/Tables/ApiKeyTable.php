<?php

namespace App\Tables;

use App\Models\PersonalAccessToken;
use App\Models\Project;

class ApiKeyTable extends AbstractTable
{
    protected string $pageName = 'apiKeysPage';

    /** @var array<int, Project> */
    protected array $projects = [];

    /**
     * @param  array<int, Project>  $projects
     */
    public function withProjects(array $projects): static
    {
        $this->projects = $projects;

        return $this;
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        $projectsById = collect($this->projects)->keyBy('id');

        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('permissions', 'Permissions')
                ->value(fn (PersonalAccessToken $token) => collect($token->abilities)
                    ->filter(fn (string $ability) => ! str_starts_with($ability, 'project:'))
                    ->contains('write') ? 'read & write' : 'read'),
            Column::make('project_ids', 'Projects')
                ->value(function (PersonalAccessToken $token) use ($projectsById) {
                    $ids = $token->getProjectIds();
                    if (empty($ids)) {
                        return ['All projects'];
                    }

                    return collect($ids)
                        /** @phpstan-ignore nullsafe.neverNull */
                        ->map(fn (int $id) => $projectsById->get($id)?->name ?? "Project #{$id}")
                        ->values()
                        ->all();
                })
                ->component('ApiKeyProjectsCell'),
            Column::make('created_at', 'Created at')
                ->sortable()
                ->date(),
            Column::data('id'),
        ];
    }
}
