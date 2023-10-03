<?php

namespace Tests\Unit\SourceControlProviders;

use App\Models\SourceControl;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitlabTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_gitlab_url(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->gitlab()
            ->create();

        $gitlab = new Gitlab($sourceControlModel);

        $this->assertSame('https://gitlab.com/api/v4', $gitlab->getApiUrl());
    }

    public function test_default_gitlab_repo_url(): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->gitlab()
            ->create();

        $gitlab = new Gitlab($sourceControlModel);

        $this->assertSame('git@gitlab.com-TEST_KEY:test/repo.git', $gitlab->fullRepoUrl($repo, $key));
    }

    /**
     * @dataProvider customUrlData
     */
    public function test_custom_url(string $url, string $expected): void
    {
        $sourceControlModel = SourceControl::factory()
            ->gitlab()
            ->create(['url' => $url]);

        $gitlab = new Gitlab($sourceControlModel);

        $this->assertSame($expected, $gitlab->getApiUrl());
    }

    /**
     * @dataProvider customRepoUrlData
     */
    public function test_custom_full_repository_url(string $url, string $expected): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->gitlab()
            ->create(['url' => $url]);

        $gitlab = new Gitlab($sourceControlModel);

        $this->assertSame($expected, $gitlab->fullRepoUrl($repo, $key));
    }

    public static function customRepoUrlData(): array
    {
        return [
            ['https://git.example.com/', 'git@git.example.com-TEST_KEY:test/repo.git'],
            ['https://git.test.example.com/', 'git@git.test.example.com-TEST_KEY:test/repo.git'],
            ['https://git.example.co.uk/', 'git@git.example.co.uk-TEST_KEY:test/repo.git'],
        ];
    }

    public static function customUrlData(): array
    {
        return [
            ['https://git.example.com/', 'https://git.example.com/api/v4'],
            ['https://git.test.example.com/', 'https://git.test.example.com/api/v4'],
            ['https://git.example.co.uk/', 'https://git.example.co.uk/api/v4'],
        ];
    }
}
