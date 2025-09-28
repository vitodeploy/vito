<?php

namespace Tests\Feature;

use App\Models\NotificationChannel;
use App\Models\User;
use App\NotificationChannels\Discord;
use App\NotificationChannels\Email;
use App\NotificationChannels\Slack;
use App\NotificationChannels\Telegram;
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
            'provider' => Email::id(),
            'email' => 'email@example.com',
            'name' => 'Email',
            'global' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Email::id())
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
            'provider' => Email::id(),
            'email' => 'email@example.com',
            'name' => 'Email',
            'global' => true,
        ]);

        /** @var ?NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Email::id())
            ->where('label', 'Email')
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_slack_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('notification-channels.store'), [
            'provider' => Slack::id(),
            'webhook_url' => 'https://hooks.slack.com/services/123/token',
            'name' => 'Slack',
        ])
            ->assertSessionDoesntHaveErrors();

        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Slack::id())
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
            'provider' => Slack::id(),
            'webhook_url' => 'https://hooks.slack.com/services/123/token',
            'name' => 'Slack',
        ])
            ->assertSessionHasErrors([
                'provider' => 'Could not connect',
            ]);

        /** @var ?NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Slack::id())
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_discord_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('notification-channels.store'), [
            'provider' => Discord::id(),
            'webhook_url' => 'https://discord.com/api/webhooks/123/token',
            'name' => 'Discord',
        ])
            ->assertSessionDoesntHaveErrors();

        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Discord::id())
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
            'provider' => Discord::id(),
            'webhook_url' => 'https://discord.com/api/webhooks/123/token',
            'name' => 'Slack',
        ])
            ->assertSessionHasErrors([
                'provider' => 'Could not connect',
            ]);

        /** @var ?NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Discord::id())
            ->first();

        $this->assertNull($channel);
    }

    public function test_add_telegram_channel(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('notification-channels.store'), [
            'provider' => Telegram::id(),
            'bot_token' => 'token',
            'chat_id' => '123',
            'name' => 'Telegram',
        ])
            ->assertSessionDoesntHaveErrors();

        /** @var NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Telegram::id())
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
            'provider' => Telegram::id(),
            'bot_token' => 'token',
            'chat_id' => '123',
            'name' => 'Telegram',
        ])
            ->assertSessionHasErrors([
                'provider' => 'Could not connect',
            ]);

        /** @var ?NotificationChannel $channel */
        $channel = NotificationChannel::query()
            ->where('provider', Telegram::id())
            ->first();

        $this->assertNull($channel);
    }

    public function test_see_channels_list(): void
    {
        $this->actingAs($this->user);

        NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('notification-channels'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('notification-channels/index'));
    }

    public function test_delete_channel(): void
    {
        $this->actingAs($this->user);

        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('notification-channels.destroy', [
            'notificationChannel' => $channel->id,
        ]));

        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }

    public function test_user_cannot_update_other_users_notification_channel(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->patch(route('notification-channels.update', $channel), [
            'name' => 'hacked',
        ])
            ->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_notification_channel(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->delete(route('notification-channels.destroy', $channel))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_notification_channels(): void
    {
        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('notification-channels'))
            ->assertRedirect('/');

        $this->post(route('notification-channels.store'), [])
            ->assertRedirect('/');

        $this->patch(route('notification-channels.update', $channel), [])
            ->assertRedirect('/');

        $this->delete(route('notification-channels.destroy', $channel))
            ->assertRedirect('/');
    }

    public function test_cannot_manipulate_user_id_on_creation(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();

        $this->post(route('notification-channels.store'), [
            'provider' => Email::id(),
            'email' => 'test@example.com',
            'name' => 'test',
            'user_id' => $otherUser->id,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('notification_channels', [
            'label' => 'test',
            'provider' => Email::id(),
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('notification_channels', [
            'label' => 'test',
            'provider' => Email::id(),
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_cannot_transfer_ownership_via_update(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'label' => 'original',
        ]);

        $this->patch(route('notification-channels.update', $channel), [
            'name' => 'updated',
            'user_id' => $otherUser->id,
        ]);

        $channel->refresh();

        $this->assertEquals($this->user->id, $channel->user_id);
        $this->assertNotEquals($otherUser->id, $channel->user_id);
    }

    public function test_user_can_only_see_own_notification_channels_in_list(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();

        $ownChannel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'label' => 'own-channel',
        ]);

        $otherChannel = NotificationChannel::factory()->create([
            'user_id' => $otherUser->id,
            'label' => 'other-channel',
        ]);

        $response = $this->get(route('notification-channels'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('notification-channels/index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->has('notificationChannels.data')
            ->where('notificationChannels.data.0.id', $ownChannel->id)
            ->whereNot('notificationChannels.data.0.id', $otherChannel->id)
        );
    }
}
