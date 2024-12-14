<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            $project->tags()->create([
                'name' => 'production',
                'color' => 'red',
            ]);
            $project->tags()->create([
                'name' => 'staging',
                'color' => 'yellow',
            ]);
        }
    }
}
