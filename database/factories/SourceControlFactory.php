<?php

namespace Database\Factories;

use App\Models\SourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\GithubApp;
use App\SourceControlProviders\Gitlab;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SourceControl>
 */
class SourceControlFactory extends Factory
{
    protected $model = SourceControl::class;

    public function definition(): array
    {
        return [
            'access_token' => Str::random(10),
            'provider' => Github::id(),
            'profile' => $this->faker->name,
            'project_id' => null,
        ];
    }

    /**
     * @return Factory<SourceControl>
     */
    public function gitlab(): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => Gitlab::id(),
        ]);
    }

    /**
     * @return Factory<SourceControl>
     */
    public function github(): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => Github::id(),
        ]);
    }

    /**
     * @return Factory<SourceControl>
     */
    public function bitbucket(): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => Bitbucket::id(),
        ]);
    }

    /**
     * @return Factory<SourceControl>
     */
    public function githubApp(): Factory
    {
        return $this->state(function (array $attributes): array {
            $login = $this->faker->userName;

            return [
                'provider' => GithubApp::id(),
                'profile' => $login,
                'external_identifier' => (string) $this->faker->unique()->numberBetween(1_000_000, 9_999_999),
                'provider_data' => [
                    'account_login' => $login,
                    'account_id' => $this->faker->unique()->numberBetween(1_000_000, 9_999_999),
                    'account_type' => 'Organization',
                    'html_url' => 'https://github.com/organizations/'.$login.'/settings/installations/123',
                ],
            ];
        });
    }
}
