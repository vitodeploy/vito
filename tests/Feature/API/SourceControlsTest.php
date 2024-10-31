<?php

namespace Tests\Feature\API;

use App\Models\SourceControl;
use App\Web\Pages\Settings\SourceControls\Widgets\SourceControlsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class SourceControlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider data
     */
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

        Livewire::test(SourceControlsList::class)
            ->callTableAction('delete', $sourceControl->id)
            ->assertNotified('This source control is being used by a site.');

        $this->assertNotSoftDeleted('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    /**
     * @dataProvider data
     */
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

    public static function data(): array
    {
        return [
            ['github', ['token' => 'test']],
            ['github', ['token' => 'test', 'global' => '1']],
            ['gitlab', ['token' => 'test']],
            ['gitlab', ['token' => 'test', 'url' => 'https://git.example.com/']],
            ['bitbucket', ['username' => 'test', 'password' => 'test']],
        ];
    }
}
