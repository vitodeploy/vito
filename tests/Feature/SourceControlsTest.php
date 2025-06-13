<?php

namespace Tests\Feature;

use App\Models\SourceControl;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SourceControlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
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

        $this->post(route('source-controls.store'), $input);

        $this->assertDatabaseHas('source_controls', [
            'provider' => $provider,
            'url' => $customUrl,
        ]);

        if (isset($input['global']) && $input['global']) {
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

    #[DataProvider('data')]
    public function test_delete_provider(string $provider): void
    {
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
        ]);

        $this->delete(route('source-controls.destroy', $sourceControl))
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('source-controls'));

        $this->assertSoftDeleted('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    #[DataProvider('data')]
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

        $this->delete(route('source-controls.destroy', $sourceControl))
            ->assertSessionHasErrors([
                'source_control' => 'This source control is being used by a site.',
            ]);

        $this->assertNotSoftDeleted('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('data')]
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

        $input['name'] = 'new-name';

        $this->patch(route('source-controls.update', $sourceControl), $input)
            ->assertSessionDoesntHaveErrors();

        $sourceControl->refresh();

        $this->assertEquals('new-name', $sourceControl->profile);
        $this->assertEquals($url, $sourceControl->url);
    }

    /**
     * @return array<int, mixed>
     */
    public static function data(): array
    {
        return [
            [Github::id(), null, ['token' => 'test']],
            [Github::id(), null, ['token' => 'test', 'global' => true]],
            [Gitlab::id(), null, ['token' => 'test']],
            [Gitlab::id(), 'https://git.example.com/', ['token' => 'test']],
            [Bitbucket::id(), null, ['username' => 'test', 'password' => 'test']],
        ];
    }
}
