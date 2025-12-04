<?php

namespace Tests\Unit\SourceControlProviders;

use App\Models\SourceControl;
use App\SourceControlProviders\Forgejo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ForgejoTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_returns_forgejo(): void
    {
        $this->assertSame('forgejo', Forgejo::id());
    }

    public function test_default_forgejo_url(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $this->assertSame('https://codeberg.org/api/v1', $forgejo->getApiUrl());
    }

    public function test_default_forgejo_repo_url(): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $this->assertSame('git@codeberg.org-TEST_KEY:test/repo.git', $forgejo->fullRepoUrl($repo, $key));
    }

    #[DataProvider('customUrlData')]
    public function test_custom_url(string $url, string $expected): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'url' => $url,
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $this->assertSame($expected, $forgejo->getApiUrl());
    }

    #[DataProvider('customRepoUrlData')]
    public function test_custom_full_repository_url(string $url, string $expected): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'url' => $url,
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $this->assertSame($expected, $forgejo->fullRepoUrl($repo, $key));
    }

    public function test_create_rules_returns_required_token_and_optional_url(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $rules = $forgejo->createRules([]);

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
                'provider' => Forgejo::id(),
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $input = [
            'token' => 'my-token',
            'url' => 'https://forgejo.example.com/',
        ];

        $data = $forgejo->createData($input);

        $this->assertSame('my-token', $data['token']);
    }

    public function test_create_data_handles_missing_input(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $data = $forgejo->createData([]);

        $this->assertSame('', $data['token']);
    }

    public function test_data_retrieves_stored_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [
                    'token' => 'stored-token',
                ],
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $data = $forgejo->data();

        $this->assertSame('stored-token', $data['token']);
    }

    public function test_data_handles_missing_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [],
                'access_token' => null,
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $data = $forgejo->data();

        $this->assertSame('', $data['token']);
    }

    public function test_get_webhook_branch_extracts_branch_from_payload(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $payload = [
            'ref' => 'refs/heads/main',
        ];

        $this->assertSame('main', $forgejo->getWebhookBranch($payload));
    }

    public function test_get_webhook_branch_returns_empty_when_missing(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $this->assertSame('', $forgejo->getWebhookBranch([]));
    }

    public function test_get_repos_returns_cached_repos_when_cache_exists(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $forgejo = new Forgejo($sourceControlModel);
        $cacheKey = 'forgejo_repos_'.md5($forgejo->getApiUrl().'test-token');
        $cachedRepos = ['user/repo1', 'user/repo2'];

        Cache::put($cacheKey, $cachedRepos, 900);

        $repos = $forgejo->getRepos();

        $this->assertSame($cachedRepos, $repos);
    }

    public function test_get_repos_fetches_from_api_when_cache_missing(): void
    {
        Http::fake([
            'codeberg.org/api/v1/user/repos*' => Http::response([
                ['full_name' => 'user/repo1'],
                ['full_name' => 'user/repo2'],
            ], 200),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $repos = $forgejo->getRepos(false);

        $this->assertSame(['user/repo1', 'user/repo2'], $repos);
    }

    public function test_get_repos_returns_empty_array_on_error(): void
    {
        Http::fake([
            'codeberg.org/api/v1/user/repos*' => Http::response([], 500),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $repos = $forgejo->getRepos(false);

        $this->assertSame([], $repos);
    }

    public function test_get_branches_returns_cached_branches_when_cache_exists(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $forgejo = new Forgejo($sourceControlModel);
        $repo = 'user/repo';
        $cacheKey = 'forgejo_branches_'.md5($repo.$forgejo->getApiUrl().'test-token');
        $cachedBranches = ['main', 'develop'];

        Cache::put($cacheKey, $cachedBranches, 900);

        $branches = $forgejo->getBranches($repo);

        $this->assertSame($cachedBranches, $branches);
    }

    public function test_get_branches_fetches_from_api_when_cache_missing(): void
    {
        Http::fake([
            'codeberg.org/api/v1/repos/user/repo/branches*' => Http::response([
                ['name' => 'main'],
                ['name' => 'develop'],
            ], 200),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $branches = $forgejo->getBranches('user/repo', false);

        $this->assertSame(['main', 'develop'], $branches);
    }

    public function test_get_branches_returns_empty_array_on_error(): void
    {
        Http::fake([
            'codeberg.org/api/v1/repos/user/repo/branches*' => Http::response([], 500),
        ]);

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => Forgejo::id(),
                'provider_data' => [
                    'token' => 'test-token',
                ],
            ]);

        $forgejo = new Forgejo($sourceControlModel);

        $branches = $forgejo->getBranches('user/repo', false);

        $this->assertSame([], $branches);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function customRepoUrlData(): array
    {
        return [
            ['https://forgejo.example.com/', 'git@forgejo.example.com-TEST_KEY:test/repo.git'],
            ['https://forgejo.test.example.com/', 'git@forgejo.test.example.com-TEST_KEY:test/repo.git'],
            ['https://forgejo.example.co.uk/', 'git@forgejo.example.co.uk-TEST_KEY:test/repo.git'],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function customUrlData(): array
    {
        return [
            ['https://forgejo.example.com/', 'https://forgejo.example.com/api/v1'],
            ['https://forgejo.test.example.com/', 'https://forgejo.test.example.com/api/v1'],
            ['https://forgejo.example.co.uk/', 'https://forgejo.example.co.uk/api/v1'],
        ];
    }
}
