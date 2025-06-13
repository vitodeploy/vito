<?php

namespace Tests\Feature\API;

use App\Models\SourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SourceControlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_connect_provider(string $provider, array $input): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        Http::fake();

        $input = array_merge([
            'name' => 'test',
            'provider' => $provider,
        ], $input);

        $this->json('POST', route('api.projects.source-controls.create', [
            'project' => $this->user->current_project_id,
        ]), $input)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => $provider,
                'name' => 'test',
            ]);
    }

    /**
     * @dataProvider data
     */
    #[DataProvider('data')]
    public function test_delete_provider(string $provider): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
        ]);

        $this->json('DELETE', route('api.projects.source-controls.delete', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    /**
     * @dataProvider data
     */
    #[DataProvider('data')]
    public function test_cannot_delete_provider(string $provider): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
        ]);

        $this->site->update([
            'source_control_id' => $sourceControl->id,
        ]);

        $this->json('DELETE', route('api.projects.source-controls.delete', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This source control is being used by a site.',
            ]);

        $this->assertNotSoftDeleted('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    /**
     * @dataProvider data
     *
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
    public function test_edit_source_control(string $provider, array $input): void
    {
        Http::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'old-name',
            'url' => $input['url'] ?? null,
        ]);

        $this->json('PUT', route('api.projects.source-controls.update', [
            'project' => $this->user->current_project_id,
            'sourceControl' => $sourceControl->id,
        ]), array_merge([
            'name' => 'new-name',
        ], $input))
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => $provider,
                'name' => 'new-name',
            ]);

        $sourceControl->refresh();

        $this->assertEquals('new-name', $sourceControl->profile);
        if (isset($input['url'])) {
            $this->assertEquals($input['url'], $sourceControl->url);
        }
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function data(): array
    {
        return [
            [Github::id(), ['token' => 'test']],
            [Github::id(), ['token' => 'test', 'global' => '1']],
            [Gitlab::id(), ['token' => 'test']],
            [Gitlab::id(), ['token' => 'test', 'url' => 'https://git.example.com/']],
            [Bitbucket::id(), ['username' => 'test', 'password' => 'test']],
        ];
    }
}
