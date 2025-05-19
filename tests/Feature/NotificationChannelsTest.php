<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_add_email_channel(): void
    {
        $this->actingAs($this->user);

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::EMAIL,
            'email' => 'email@example.com',
            'name' => 'Email',
            'global' => true,
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::EMAIL,
            'email' => 'email@example.com',
            'name' => 'Email',
            'global' => true,
        ]);

        /** @var ?\App\Models\NotificationChannel $channel */
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

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::SLACK,
            'webhook_url' => 'https://hooks.slack.com/services/123/token',
            'name' => 'Slack',
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::SLACK,
            'webhook_url' => 'https://hooks.slack.com/services/123/token',
            'name' => 'Slack',
        ])
            ->assertSessionHasErrors([
                'provider' => 'Could not connect',
            ]);

        /** @var ?\App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::SLACK)
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_discord_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::DISCORD,
            'webhook_url' => 'https://discord.com/api/webhooks/123/token',
            'name' => 'Discord',
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::DISCORD,
            'webhook_url' => 'https://discord.com/api/webhooks/123/token',
            'name' => 'Slack',
        ])
            ->assertSessionHasErrors([
                'provider' => 'Could not connect',
            ]);

        /** @var ?\App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::DISCORD)
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_telegram_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::TELEGRAM,
            'bot_token' => 'token',
            'chat_id' => '123',
            'name' => 'Telegram',
        ])
            ->assertSessionDoesntHaveErrors();

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

        $this->post(route('notification-channels.store'), [
            'provider' => NotificationChannel::TELEGRAM,
            'bot_token' => 'token',
            'chat_id' => '123',
            'name' => 'Telegram',
        ])
            ->assertSessionHasErrors([
                'provider' => 'Could not connect',
            ]);

        /** @var ?\App\Models\NotificationChannel $channel */
        $channel = \App\Models\NotificationChannel::query()
            ->where('provider', NotificationChannel::TELEGRAM)
            ->first();

        $this->assertNull($channel);
    }

    public function test_see_channels_list(): void
    {
        $this->actingAs($this->user);

        \App\Models\NotificationChannel::factory()->create();

        $this->get(route('notification-channels'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('notification-channels/index'));
    }

    public function test_delete_channel(): void
    {
        $this->actingAs($this->user);

        $channel = \App\Models\NotificationChannel::factory()->create();

        $this->delete(route('notification-channels.destroy', [
            'notificationChannel' => $channel->id,
        ]));

        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }
}
