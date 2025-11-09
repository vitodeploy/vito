<?php

namespace Tests\Unit\SourceControlProviders;

use App\Models\SourceControl;
use App\SourceControlProviders\Gitea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GiteaTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_returns_gitea(): void
    {
        $this->assertSame('gitea', Gitea::id());
    }

    public function test_default_gitea_url(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
            ]);

        $gitea = new Gitea($sourceControlModel);

        $this->assertSame('https://gitea.com/api/v1', $gitea->getApiUrl());
    }

    public function test_default_gitea_repo_url(): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
            ]);

        $gitea = new Gitea($sourceControlModel);

        $this->assertSame('git@gitea.com-TEST_KEY:test/repo.git', $gitea->fullRepoUrl($repo, $key));
    }

    #[DataProvider('customUrlData')]
    public function test_custom_url(string $url, string $expected): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'url' => $url,
            ]);

        $gitea = new Gitea($sourceControlModel);

        $this->assertSame($expected, $gitea->getApiUrl());
    }

    #[DataProvider('customRepoUrlData')]
    public function test_custom_full_repository_url(string $url, string $expected): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'url' => $url,
            ]);

        $gitea = new Gitea($sourceControlModel);

        $this->assertSame($expected, $gitea->fullRepoUrl($repo, $key));
    }

    public function test_create_rules_returns_required_token_and_optional_url(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
            ]);

        $gitea = new Gitea($sourceControlModel);

        $rules = $gitea->createRules([]);

        $this->assertArrayHasKey('token', $rules);
        $this->assertSame('required', $rules['token']);
        $this->assertArrayHasKey('url', $rules);
        $this->assertIsArray($rules['url']);
        $this->assertContains('nullable', $rules['url']);
        $this->assertContains('url:http,https', $rules['url']);
        $this->assertContains('ends_with:/', $rules['url']);
    }

    public function test_create_data_processes_input_correctly(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
            ]);

        $gitea = new Gitea($sourceControlModel);

        $input = [
            'token' => 'my-token',
            'url' => 'https://git.example.com/',
        ];

        $data = $gitea->createData($input);

        $this->assertSame('my-token', $data['token']);
    }

    public function test_create_data_handles_missing_input(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
            ]);

        $gitea = new Gitea($sourceControlModel);

        $data = $gitea->createData([]);

        $this->assertSame('', $data['token']);
    }

    public function test_data_retrieves_stored_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [
                    'token' => 'stored-token',
                ],
            ]);

        $gitea = new Gitea($sourceControlModel);

        $data = $gitea->data();

        $this->assertSame('stored-token', $data['token']);
    }

    public function test_data_handles_missing_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [],
                'access_token' => null,
            ]);

        $gitea = new Gitea($sourceControlModel);

        $data = $gitea->data();

        $this->assertSame('', $data['token']);
    }

    public function test_get_webhook_branch_extracts_branch_from_payload(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
            ]);

        $gitea = new Gitea($sourceControlModel);

        $payload = [
            'ref' => 'refs/heads/main',
        ];

        $this->assertSame('main', $gitea->getWebhookBranch($payload));
    }

    public function test_get_webhook_branch_returns_empty_when_missing(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
            ]);

        $gitea = new Gitea($sourceControlModel);

        $this->assertSame('', $gitea->getWebhookBranch([]));
    }

    public function test_get_repos_returns_cached_repos_when_cache_exists(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $gitea = new Gitea($sourceControlModel);
        $cacheKey = 'gitea_repos_'.md5($gitea->getApiUrl().'test-token');
        $cachedRepos = ['user/repo1', 'user/repo2'];

        Cache::put($cacheKey, $cachedRepos, 900);

        $repos = $gitea->getRepos();

        $this->assertSame($cachedRepos, $repos);
    }

    public function test_get_repos_fetches_from_api_when_cache_missing(): void
    {
        Http::fake([
            'gitea.com/api/v1/user/repos*' => Http::response([
                ['full_name' => 'user/repo1'],
                ['full_name' => 'user/repo2'],
            ], 200),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $gitea = new Gitea($sourceControlModel);

        $repos = $gitea->getRepos(false);

        $this->assertSame(['user/repo1', 'user/repo2'], $repos);
    }

    public function test_get_repos_returns_empty_array_on_error(): void
    {
        Http::fake([
            'gitea.com/api/v1/user/repos*' => Http::response([], 500),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $gitea = new Gitea($sourceControlModel);

        $repos = $gitea->getRepos(false);

        $this->assertSame([], $repos);
    }

    public function test_get_branches_returns_cached_branches_when_cache_exists(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $gitea = new Gitea($sourceControlModel);
        $repo = 'user/repo';
        $cacheKey = 'gitea_branches_'.md5($repo.$gitea->getApiUrl().'test-token');
        $cachedBranches = ['main', 'develop'];

        Cache::put($cacheKey, $cachedBranches, 900);

        $branches = $gitea->getBranches($repo);

        $this->assertSame($cachedBranches, $branches);
    }

    public function test_get_branches_fetches_from_api_when_cache_missing(): void
    {
        Http::fake([
            'gitea.com/api/v1/repos/user/repo/branches*' => Http::response([
                ['name' => 'main'],
                ['name' => 'develop'],
            ], 200),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $gitea = new Gitea($sourceControlModel);

        $branches = $gitea->getBranches('user/repo', false);

        $this->assertSame(['main', 'develop'], $branches);
    }

    public function test_get_branches_returns_empty_array_on_error(): void
    {
        Http::fake([
            'gitea.com/api/v1/repos/user/repo/branches*' => Http::response([], 500),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Gitea::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $gitea = new Gitea($sourceControlModel);

        $branches = $gitea->getBranches('user/repo', false);

        $this->assertSame([], $branches);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function customRepoUrlData(): array
    {
        return [
            ['https://git.example.com/', 'git@git.example.com-TEST_KEY:test/repo.git'],
            ['https://git.test.example.com/', 'git@git.test.example.com-TEST_KEY:test/repo.git'],
            ['https://git.example.co.uk/', 'git@git.example.co.uk-TEST_KEY:test/repo.git'],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function customUrlData(): array
    {
        return [
            ['https://git.example.com/', 'https://git.example.com/api/v1'],
            ['https://git.test.example.com/', 'https://git.test.example.com/api/v1'],
            ['https://git.example.co.uk/', 'https://git.example.co.uk/api/v1'],
        ];
    }
}
