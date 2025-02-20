<?php

namespace App\Cli\Commands;

use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;

class SetupCommand extends Command
{
    protected $signature = 'setup';

    protected $description = 'Setup the application';

    public function handle(): void
    {
        $this->prepareStorage();

        $this->migrate();

        $this->makeUser();

        $this->makeProject();

        info('The application has been setup');
    }

    private function prepareStorage(): void
    {
        File::ensureDirectoryExists(storage_path());
    }

    private function migrate(): void
    {
        $this->call('migrate', ['--force' => true]);
    }

    private function makeUser(): void
    {
        $user = User::query()->first();
        if ($user) {
            return;
        }

        $name = text(
            label: 'What is your name?',
            required: true,
        );
        $email = text(
            label: 'What is your email?',
            required: true,
        );

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(str()->random(16)),
        ]);
    }

    private function makeProject(): void
    {
        $project = Project::query()->first();

        if ($project) {
            return;
        }

        $project = Project::query()->create([
            'name' => 'default',
        ]);

        $user = User::query()->first();
        $user->update([
            'current_project_id' => $project->id,
        ]);
    }
}
