<?php

namespace Database\Factories;

use App\Models\IsolatedUser;
use App\Models\Site;
use App\SiteTypes\Laravel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'server_id' => 1,
            'type' => Laravel::id(),
            'domain' => 'test.com',
            'web_directory' => '/',
            'path' => '/home',
            'status' => 'ready',
            'progress' => '100',
            'php_version' => '8.2',
            'branch' => 'main',
            'user' => 'vito',
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Site $site): void {
            if ($site->isolated_user_id !== null) {
                return;
            }

            $rawUser = $site->getRawOriginal('user');
            if (! is_string($rawUser) || $rawUser === '' || $rawUser === $site->server->getSshUser()) {
                return;
            }

            $iuser = IsolatedUser::query()->firstOrCreate(
                ['server_id' => $site->server_id, 'username' => $rawUser],
            );

            $site->isolated_user_id = $iuser->id;
            $site->saveQuietly();
        });
    }
}
