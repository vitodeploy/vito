<?php

namespace Tests\Feature\Http;

use App\Http\Livewire\SourceControls\Connect;
use App\Http\Livewire\SourceControls\SourceControlsList;
use App\Models\SourceControl;
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
    public function test_connect_provider(string $provider, ?string $customUrl): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $livewire = Livewire::test(Connect::class)
            ->set('token', 'token')
            ->set('name', 'profile')
            ->set('provider', $provider);

        if ($customUrl !== null) {
            $livewire->set('url', $customUrl);
        }

        $livewire
            ->call('connect')
            ->assertSuccessful();

        $this->assertDatabaseHas('source_controls', [
            'provider' => $provider,
            'url' => $customUrl,
        ]);
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
            ->set('deleteId', $sourceControl->id)
            ->call('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('source_controls', [
            'id' => $sourceControl->id,
        ]);
    }

    public static function data(): array
    {
        return [
            ['github', null],
            ['gitlab', null],
            ['gitlab', 'https://git.example.com/'],
            ['bitbucket', null],
        ];
    }
}
