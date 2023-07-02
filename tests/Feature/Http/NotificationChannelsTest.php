<?php

namespace Tests\Feature\Http;

use App\Enums\NotificationChannel;
use App\Http\Livewire\NotificationChannels\AddChannel;
use App\Http\Livewire\NotificationChannels\ChannelsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_add_email_channel(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AddChannel::class)
            ->set('provider', NotificationChannel::EMAIL)
            ->set('email', 'email@example.com')
            ->call('add')
            ->assertSuccessful();
    }

    public function test_add_slack_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(AddChannel::class)
            ->set('provider', NotificationChannel::SLACK)
            ->set('label', 'Slack')
            ->set('webhook_url', $this->faker->url)
            ->call('add')
            ->assertSuccessful();
    }

    public function test_add_discord_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(AddChannel::class)
            ->set('provider', NotificationChannel::DISCORD)
            ->set('label', 'Slack')
            ->set('webhook_url', $this->faker->url)
            ->call('add')
            ->assertSuccessful();
    }

    public function test_see_channels_list(): void
    {
        $this->actingAs($this->user);

        $channel = \App\Models\NotificationChannel::factory()->create();

        Livewire::test(ChannelsList::class)
            ->assertSee([
                $channel->provider,
            ]);
    }

    public function test_delete_channel(): void
    {
        $this->actingAs($this->user);

        $channel = \App\Models\NotificationChannel::factory()->create();

        Livewire::test(ChannelsList::class)
            ->set('deleteId', $channel->id)
            ->call('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }
}
