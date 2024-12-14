<?php

namespace Database\Seeders;

use App\Models\SourceControl;
use Illuminate\Database\Seeder;

class SourceControlsSeeder extends Seeder
{
    public function run(): void
    {
        SourceControl::factory()->create([
            'profile' => 'GitHub',
            'provider' => \App\Enums\SourceControl::GITHUB,
            'provider_data' => [
                'token' => 'github_token',
            ],
        ]);

        SourceControl::factory()->create([
            'profile' => 'GitLab',
            'provider' => \App\Enums\SourceControl::GITLAB,
            'provider_data' => [
                'token' => 'gitlab_token',
            ],
        ]);

        SourceControl::factory()->create([
            'profile' => 'Bitbucket',
            'provider' => \App\Enums\SourceControl::BITBUCKET,
            'provider_data' => [
                'username' => 'bitbucket_username',
                'password' => 'bitbucket_password',
            ],
        ]);
    }
}
