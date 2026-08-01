<?php

use App\Models\NotificationChannel;
use App\Models\User;
use App\NotificationChannels\Discord;
use App\NotificationChannels\Email;
use App\NotificationChannels\Slack;
use App\NotificationChannels\Telegram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('add email channel', function () {
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

    expect($channel->data['email'])->toEqual('email@example.com');
    expect($channel->connected)->toBeTrue();
});

test('cannot add email channel', function () {
    config()->set('mail.default', 'smtp');
    config()->set('mail.mailers.smtp.host', '127.0.0.1');
    config()->set('mail.mailers.smtp.port', 1);

    // closed port so the SMTP connection fails even if a local mail catcher (e.g. Mailpit) is listening
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

    expect($channel)->toBeNull();
});

test('add slack channel', function () {
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

    expect($channel->data['webhook_url'])->toEqual('https://hooks.slack.com/services/123/token');
    expect($channel->connected)->toBeTrue();
});

test('cannot add slack channel', function () {
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

    expect($channel)->toBeNull();
});

test('add discord channel', function () {
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

    expect($channel->data['webhook_url'])->toEqual('https://discord.com/api/webhooks/123/token');
    expect($channel->connected)->toBeTrue();
});

test('cannot add discord channel', function () {
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

    expect($channel)->toBeNull();
});

test('add telegram channel', function () {
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

    expect($channel->data['chat_id'])->toEqual('123');
    expect($channel->data['bot_token'])->toEqual('token');
    expect($channel->connected)->toBeTrue();
});

test('cannot add telegram channel', function () {
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

    expect($channel)->toBeNull();
});

test('see channels list', function () {
    $this->actingAs($this->user);

    NotificationChannel::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->get(route('notification-channels'))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('notification-channels/index'));
});

test('delete channel', function () {
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
});

test('user cannot update other users notification channel', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $channel = NotificationChannel::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->patch(route('notification-channels.update', $channel), [
        'name' => 'hacked',
    ])
        ->assertForbidden();
});

test('user cannot delete other users notification channel', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $channel = NotificationChannel::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->delete(route('notification-channels.destroy', $channel))
        ->assertForbidden();
});

test('guest cannot access notification channels', function () {
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
});

test('cannot manipulate user id on creation', function () {
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
});

test('cannot transfer ownership via update', function () {
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

    expect($channel->user_id)->toEqual($this->user->id);
    $this->assertNotEquals($otherUser->id, $channel->user_id);
});

test('user can only see own notification channels in list', function () {
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
});
