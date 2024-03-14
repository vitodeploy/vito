<?php

namespace Tests\Feature;

use App\Models\SourceControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use JsonException;
use Tests\TestCase;

class SourceControlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider data
     *
     * @throws JsonException
     */
    public function test_connect_provider(string $provider, ?string $customUrl): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $input = [
            'name' => 'test',
            'provider' => $provider,
            'token' => 'token',
        ];

        if ($customUrl !== null) {
            $input['url'] = $customUrl;
        }
        $this->post(route('source-controls.connect'), $input)
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('source_controls', [
            'provider' => $provider,
            'url' => $customUrl,
        ]);
    }

    /**
     * @dataProvider data
     *
     * @throws JsonException
     */
    public function test_delete_provider(string $provider): void
    {
        $this->actingAs($this->user);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => $provider,
            'profile' => 'test',
        ]);

        $this->delete(route('source-controls.delete', $sourceControl->id))
            ->assertSessionDoesntHaveErrors();

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
