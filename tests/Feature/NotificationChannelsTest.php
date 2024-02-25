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

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::EMAIL)
            ->first();

        $this->assertEquals('email@example.com', $channel->data['email']);
        $this->assertTrue($channel->connected);
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

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::SLACK)
            ->first();

        $this->assertEquals('https://hooks.slack.com/services/123/token', $channel->data['webhook_url']);
        $this->assertTrue($channel->connected);
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

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::DISCORD)
            ->first();

        $this->assertEquals('https://discord.com/api/webhooks/123/token', $channel->data['webhook_url']);
        $this->assertTrue($channel->connected);
    }

    /*
     * @TODO fix json comparison
     */
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

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::TELEGRAM)
            ->first();

        $this->assertEquals('123', $channel->data['chat_id']);
        $this->assertEquals('token', $channel->data['bot_token']);
        $this->assertTrue($channel->connected);
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
