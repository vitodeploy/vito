<?php

namespace Tests\Unit\SourceControlProviders;

use App\Models\SourceControl;
use App\SourceControlProviders\BitbucketV2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BitbucketV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_id_returns_bitbucket_v2(): void
    {
        $this->assertSame('bitbucket-v2', BitbucketV2::id());
    }

    public function test_default_bitbucket_repo_url(): void
    {
        $repo = 'test/repo';
        $key = 'TEST_KEY';

        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $this->assertSame('git@bitbucket.org-TEST_KEY:test/repo.git', $bitbucketV2->fullRepoUrl($repo, $key));
    }

    public function test_create_rules_returns_required_key_and_secret(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $rules = $bitbucketV2->createRules([]);

        $this->assertArrayHasKey('key', $rules);
        $this->assertArrayHasKey('secret', $rules);
        $this->assertSame('required', $rules['key']);
        $this->assertSame('required', $rules['secret']);
    }

    public function test_create_data_processes_input_correctly(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $input = [
            'key' => 'my-key',
            'secret' => 'my-secret',
        ];

        $data = $bitbucketV2->createData($input);

        $this->assertSame('my-key', $data['key']);
        $this->assertSame('my-secret', $data['secret']);
    }

    public function test_create_data_handles_missing_input(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $data = $bitbucketV2->createData([]);

        $this->assertSame('', $data['key']);
        $this->assertSame('', $data['secret']);
    }

    public function test_data_retrieves_stored_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [
                    'key' => 'stored-key',
                    'secret' => 'stored-secret',
                ],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $data = $bitbucketV2->data();

        $this->assertSame('stored-key', $data['key']);
        $this->assertSame('stored-secret', $data['secret']);
    }

    public function test_data_handles_missing_provider_data(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $data = $bitbucketV2->data();

        $this->assertSame('', $data['key']);
        $this->assertSame('', $data['secret']);
    }

    public function test_get_webhook_branch_extracts_branch_from_payload(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $payload = [
            'push' => [
                'changes' => [
                    [
                        'new' => [
                            'name' => 'main',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame('main', $bitbucketV2->getWebhookBranch($payload));
    }

    public function test_get_webhook_branch_returns_default_when_missing(): void
    {
        $sourceControlModel = SourceControl::factory()
            ->create([
                'provider' => BitbucketV2::id(),
                'provider_data' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
            ]);

        $bitbucketV2 = new BitbucketV2($sourceControlModel);

        $this->assertSame('default-branch', $bitbucketV2->getWebhookBranch([]));
    }
}
