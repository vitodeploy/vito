<?php

namespace Tests\Feature;

use App\Models\SourceControl;
use App\Web\Pages\Settings\SourceControls\Index;
use App\Web\Pages\Settings\SourceControls\Widgets\SourceControlsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
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

        Livewire::test(Index::class)
            ->callAction('connect', $input)
            ->assertSuccessful();

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

        Livewire::test(SourceControlsList::class)
            ->callTableAction('delete', $sourceControl->id)
            ->assertSuccessful();

        $this->assertSoftDeleted('source_controls', [
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

        Livewire::test(SourceControlsList::class)
            ->callTableAction('edit', $sourceControl->id, [
                'name' => 'new-name',
                'token' => 'test', // for GitHub and Gitlab
                'username' => 'test', // for Bitbucket
                'password' => 'test', // for Bitbucket
                'url' => $url, // for Gitlab
            ])
            ->assertSuccessful();

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
