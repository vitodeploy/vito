<?php

namespace Database\Seeders;

use App\Models\SourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Database\Seeder;

class SourceControlsSeeder extends Seeder
{
    public function run(): void
    {
        SourceControl::factory()->create([
            'profile' => 'GitHub',
            'provider' => Github::id(),
            'provider_data' => [
                'token' => 'github_token',
            ],
        ]);

        SourceControl::factory()->create([
            'profile' => 'GitLab',
            'provider' => Gitlab::id(),
            'provider_data' => [
                'token' => 'gitlab_token',
            ],
        ]);

        SourceControl::factory()->create([
            'profile' => 'Bitbucket',
            'provider' => Bitbucket::id(),
            'provider_data' => [
                'username' => 'bitbucket_username',
                'password' => 'bitbucket_password',
            ],
        ]);
    }
}
