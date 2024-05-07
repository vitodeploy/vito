<?php

namespace Tests\Feature;

use App\Models\SourceControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SourceControlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider data
     */
    public function test_connect_provider(string $provider, ?string $customUrl, array $input): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $input = array_merge([
            'name' => 'test',
            'provider' => $provider,
        ], $input);

        if ($customUrl !== null) {
            $input['url'] = $customUrl;
        }
        $this->post(route('settings.source-controls.connect'), $input)
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('source_controls', [
            'provider' => $provider,
            'url' => $customUrl,
        ]);

        if (isset($input['global'])) {
            $this->assertDatabaseHas('source_controls', [
                'provider' => $provider,
                'url' => $customUrl,
                'project_id' => null,
            ]);
        } else {
            $this->assertDatabaseHas('source_controls', [
                'provider' => $provider,
                'url' => $customUrl,
                'project_id' => $this->user->current_project_id,
            ]);
        }
    }

    /**
     * @dataProvider data
     */
    public function test_delete_provider(string $provider): void
    {
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
        ]);

        $this->delete(route('settings.source-controls.delete', $sourceControl->id))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    /**
     * @dataProvider data
     */
    public function test_cannot_delete_provider(string $provider): void
    {
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
        ]);

        $this->site->update([
            'source_control_id' => $sourceControl->id,
        ]);

        $this->delete(route('settings.source-controls.delete', $sourceControl->id))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'This source control is being used by a site.');

        $this->assertDatabaseHas('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    /**
     * @dataProvider data
     */
    public function test_edit_source_control(string $provider, ?string $url, array $input): void
    {
        Http::fake();

        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'old-name',
            'url' => $url,
        ]);

        $this->post(route('settings.source-controls.update', $sourceControl->id), array_merge([
            'name' => 'new-name',
            'url' => $url,
        ], $input))->assertSessionDoesntHaveErrors();

        $sourceControl->refresh();

        $this->assertEquals('new-name', $sourceControl->profile);
        $this->assertEquals($url, $sourceControl->url);
    }

    public static function data(): array
    {
        return [
            ['github', null, ['token' => 'test']],
            ['github', null, ['token' => 'test', 'global' => '1']],
            ['gitlab', null, ['token' => 'test']],
            ['gitlab', 'https://git.example.com/', ['token' => 'test']],
            ['bitbucket', null, ['username' => 'test', 'password' => 'test']],
        ];
    }
}
