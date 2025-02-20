<?php

namespace App\Cli\Commands\Projects;

use App\Cli\Commands\AbstractCommand;
use App\Models\Project;

use function Laravel\Prompts\table;

class ProjectsListCommand extends AbstractCommand
{
    protected $signature = 'projects:list';

    protected $description = 'Show projects list';

    public function handle(): void
    {
        $projects = Project::all();

        table(
            headers: ['ID', 'Name', 'Selected'],
            rows: $projects->map(fn (Project $project) => [
                $project->id,
                $project->name,
                $project->id === $this->user()->current_project_id ? 'Yes' : 'No',
            ])->toArray(),
        );
    }
}
