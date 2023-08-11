<?php

namespace Tests\Feature\Http;

use App\Http\Livewire\SourceControls\Bitbucket;
use App\Http\Livewire\SourceControls\Connect;
use App\Http\Livewire\SourceControls\Github;
use App\Http\Livewire\SourceControls\Gitlab;
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
    public function test_connect_provider(string $provider): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(Connect::class)
            ->set('token', 'token')
            ->set('name', 'profile')
            ->set('provider', $provider)
            ->call('connect')
            ->assertSuccessful();

        $this->assertDatabaseHas('source_controls', [
            'provider' => $provider,
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
            'profile' => 'test'
        ]);

        Livewire::test(SourceControlsList::class)
            ->set('deleteId', $sourceControl->id)
            ->call('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('source_controls', [
            'provider' => $provider,
        ]);
    }

    public static function data(): array
    {
        return [
            ['github'],
            ['gitlab'],
            ['bitbucket'],
        ];
    }
}
