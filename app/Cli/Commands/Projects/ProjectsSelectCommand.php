<?php

namespace App\Cli\Commands\Projects;

use App\Cli\Commands\AbstractCommand;
use App\Models\Project;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ProjectsSelectCommand extends AbstractCommand
{
    protected $signature = 'projects:select {project}';

    protected $description = 'Select a project';

    public function handle(): void
    {
        $project = Project::query()->find($this->argument('project'));

        if (! $project) {
            error('The project does not exist');

            return;
        }

        $this->user()->update([
            'current_project_id' => $project->id,
        ]);

        info(__('The project [:project] has been selected' , [
            'project' => $project->name,
        ]));
    }
}
