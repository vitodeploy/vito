<?php

namespace Tests\Feature\Http;

use App\Http\Livewire\SourceControls\Bitbucket;
use App\Http\Livewire\SourceControls\Github;
use App\Http\Livewire\SourceControls\Gitlab;
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
    public function test_connect_provider(string $provider, string $component): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test($component)
            ->set('token', 'token')
            ->call('connect')
            ->assertSuccessful();

        $this->assertDatabaseHas('source_controls', [
            'provider' => $provider,
        ]);
    }

    /**
     * @dataProvider data
     */
    public function test_delete_provider(string $provider, string $component): void
    {
        $this->actingAs($this->user);

        SourceControl::factory()->create([
            'provider' => $provider,
        ]);

        Livewire::test($component)
            ->set('token', '')
            ->call('connect')
            ->assertSuccessful();

        $this->assertDatabaseMissing('source_controls', [
            'provider' => $provider,
        ]);
    }

    public static function data(): array
    {
        return [
            ['github', Github::class],
            ['gitlab', Gitlab::class],
            ['bitbucket', Bitbucket::class],
        ];
    }
}
