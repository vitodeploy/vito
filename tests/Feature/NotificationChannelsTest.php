<?php

namespace Tests\Feature;

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
            ->set('label', 'Email')
            ->call('add')
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_channels', [
            'provider' => NotificationChannel::EMAIL,
            'data' => cast_to_json([
                'email' => 'email@example.com',
            ]),
            'connected' => 1,
        ]);
    }

    public function test_add_slack_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(AddChannel::class)
            ->set('provider', NotificationChannel::SLACK)
            ->set('label', 'Slack')
            ->set('webhook_url', 'https://hooks.slack.com/services/123/token')
            ->call('add')
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_channels', [
            'provider' => NotificationChannel::SLACK,
            'data' => cast_to_json([
                'webhook_url' => 'https://hooks.slack.com/services/123/token',
            ]),
            'connected' => 1,
        ]);
    }

    public function test_add_discord_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(AddChannel::class)
            ->set('provider', NotificationChannel::DISCORD)
            ->set('label', 'Discord')
            ->set('webhook_url', 'https://discord.com/api/webhooks/123/token')
            ->call('add')
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_channels', [
            'provider' => NotificationChannel::DISCORD,
            'data' => cast_to_json([
                'webhook_url' => 'https://discord.com/api/webhooks/123/token',
            ]),
            'connected' => 1,
        ]);
    }

    public function test_add_telegram_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(AddChannel::class)
            ->set('provider', NotificationChannel::TELEGRAM)
            ->set('label', 'Telegram')
            ->set('bot_token', 'token')
            ->set('chat_id', '123')
            ->call('add')
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_channels', [
            'provider' => NotificationChannel::TELEGRAM,
            'data' => cast_to_json([
                'chat_id' => '123',
                'bot_token' => 'token',
            ]),
            'connected' => 1,
        ]);
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
