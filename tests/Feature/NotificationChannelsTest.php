<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_add_email_channel(): void
    {
        $this->actingAs($this->user);

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::EMAIL,
            'email' => 'email@example.com',
            'label' => 'Email',
            'global' => 1,
        ])->assertSessionDoesntHaveErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::EMAIL)
            ->where('label', 'Email')
            ->whereNull('project_id')
            ->first();

        $this->assertEquals('email@example.com', $channel->data['email']);
        $this->assertTrue($channel->connected);
    }

    public function test_cannot_add_email_channel(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.host', '127.0.0.1'); // invalid host

        $this->actingAs($this->user);

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::EMAIL,
            'email' => 'email@example.com',
            'label' => 'Email',
        ])->assertSessionHasErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::EMAIL)
            ->where('label', 'Email')
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_slack_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::SLACK,
            'webhook_url' => 'https://hooks.slack.com/services/123/token',
            'label' => 'Slack',
        ])->assertSessionDoesntHaveErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::SLACK)
            ->first();

        $this->assertEquals('https://hooks.slack.com/services/123/token', $channel->data['webhook_url']);
        $this->assertTrue($channel->connected);
    }

    public function test_cannot_add_slack_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'slack.com/*' => Http::response(['ok' => false], 401),
        ]);

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::SLACK,
            'webhook_url' => 'https://hooks.slack.com/services/123/token',
            'label' => 'Slack',
        ])->assertSessionHasErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::SLACK)
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_discord_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::DISCORD,
            'webhook_url' => 'https://discord.com/api/webhooks/123/token',
            'label' => 'Discord',
        ])->assertSessionDoesntHaveErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::DISCORD)
            ->first();

        $this->assertEquals('https://discord.com/api/webhooks/123/token', $channel->data['webhook_url']);
        $this->assertTrue($channel->connected);
    }

    public function test_cannot_add_discord_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'discord.com/*' => Http::response(['ok' => false], 401),
        ]);

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::DISCORD,
            'webhook_url' => 'https://discord.com/api/webhooks/123/token',
            'label' => 'Discord',
        ])->assertSessionHasErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::DISCORD)
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_telegram_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::TELEGRAM,
            'bot_token' => 'token',
            'chat_id' => '123',
            'label' => 'Telegram',
        ])->assertSessionDoesntHaveErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::TELEGRAM)
            ->first();

        $this->assertEquals('123', $channel->data['chat_id']);
        $this->assertEquals('token', $channel->data['bot_token']);
        $this->assertTrue($channel->connected);
    }

    public function test_cannot_add_telegram_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 401),
        ]);

        $this->post(route('settings.notification-channels.add'), [
            'provider' => NotificationChannel::TELEGRAM,
            'bot_token' => 'token',
            'chat_id' => '123',
            'label' => 'Telegram',
        ])->assertSessionHasErrors();

        /** @var \App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::TELEGRAM)
            ->first();

        $this->assertNull($channel);
    }

    public function test_see_channels_list(): void
    {
        $this->actingAs($this->user);

        $channel = \App\Models\NotificationChannel::factory()->create();

        $this->get(route('settings.notification-channels'))
            ->assertSuccessful()
            ->assertSee($channel->provider);
    }

    public function test_delete_channel(): void
    {
        $this->actingAs($this->user);

        $channel = \App\Models\NotificationChannel::factory()->create();

        $this->delete(route('settings.notification-channels.delete', $channel->id))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }
}
