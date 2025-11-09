<?php

namespace Tests\Unit\SourceControlProviders;

use App\Models\SourceControl;
use App\SourceControlProviders\Github;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GithubTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_returns_github(): void
    {
        $this->assertSame('github', Github::id());
    }

    public function test_default_github_repo_url(): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create();

        $github = new Github($sourceControlModel);

        $this->assertSame('git@github.com-TEST_KEY:test/repo.git', $github->fullRepoUrl($repo, $key));
    }

    public function test_create_rules_returns_required_token(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create();

        $github = new Github($sourceControlModel);

        $rules = $github->createRules([]);

        $this->assertArrayHasKey('token', $rules);
        $this->assertSame('required', $rules['token']);
    }

    public function test_create_data_processes_input_correctly(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create();

        $github = new Github($sourceControlModel);

        $input = [
            'token' => 'my-token',
        ];

        $data = $github->createData($input);

        $this->assertSame('my-token', $data['token']);
    }

    public function test_create_data_handles_missing_input(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create();

        $github = new Github($sourceControlModel);

        $data = $github->createData([]);

        $this->assertSame('', $data['token']);
    }

    public function test_data_retrieves_stored_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [
                    'token' => 'stored-token',
                ],
            ]);

        $github = new Github($sourceControlModel);

        $data = $github->data();

        $this->assertSame('stored-token', $data['token']);
    }

    public function test_data_handles_missing_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [],
                'access_token' => null,
            ]);

        $github = new Github($sourceControlModel);

        $data = $github->data();

        $this->assertSame('', $data['token']);
    }

    public function test_get_webhook_branch_extracts_branch_from_payload(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create();

        $github = new Github($sourceControlModel);

        $payload = [
            'ref' => 'refs/heads/main',
        ];

        $this->assertSame('main', $github->getWebhookBranch($payload));
    }

    public function test_get_webhook_branch_returns_empty_when_missing(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create();

        $github = new Github($sourceControlModel);

        $this->assertSame('', $github->getWebhookBranch([]));
    }

    public function test_get_repos_returns_cached_repos_when_cache_exists(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $github = new Github($sourceControlModel);
        $cacheKey = 'github_repos_'.md5('test-token');
        $cachedRepos = ['user/repo1', 'user/repo2'];

        Cache::put($cacheKey, $cachedRepos, 900);

        $repos = $github->getRepos();

        $this->assertSame($cachedRepos, $repos);
    }

    public function test_get_repos_fetches_from_api_when_cache_missing(): void
    {
        Http::fake([
            'api.github.com/user/repos*' => Http::sequence()
                ->push([
                    ['full_name' => 'user/repo1'],
                    ['full_name' => 'user/repo2'],
                ], 200, ['Link' => '']),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $github = new Github($sourceControlModel);

        $repos = $github->getRepos(false);

        $this->assertSame(['user/repo1', 'user/repo2'], $repos);
    }

    public function test_get_repos_returns_empty_array_on_error(): void
    {
        Http::fake([
            'api.github.com/user/repos*' => Http::response([], 500),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $github = new Github($sourceControlModel);

        $repos = $github->getRepos(false);

        $this->assertSame([], $repos);
    }

    public function test_get_branches_returns_cached_branches_when_cache_exists(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $github = new Github($sourceControlModel);
        $repo = 'user/repo';
        $cacheKey = 'github_branches_'.md5($repo.'test-token');
        $cachedBranches = ['main', 'develop'];

        Cache::put($cacheKey, $cachedBranches, 900);

        $branches = $github->getBranches($repo);

        $this->assertSame($cachedBranches, $branches);
    }

    public function test_get_branches_fetches_from_api_when_cache_missing(): void
    {
        Http::fake([
            'api.github.com/repos/user/repo/branches*' => Http::sequence()
                ->push([
                    ['name' => 'main'],
                    ['name' => 'develop'],
                ], 200, ['Link' => '']),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $github = new Github($sourceControlModel);

        $branches = $github->getBranches('user/repo', false);

        $this->assertSame(['main', 'develop'], $branches);
    }

    public function test_get_branches_returns_empty_array_on_error(): void
    {
        Http::fake([
            'api.github.com/repos/user/repo/branches*' => Http::response([], 500),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->github()
            ->create([
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $github = new Github($sourceControlModel);

        $branches = $github->getBranches('user/repo', false);

        $this->assertSame([], $branches);
    }
}
