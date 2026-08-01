<?php

use App\Models\NotificationChannel;
use App\NotificationChannels\Discord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Unit\NotificationChannels\TestNotification;

uses(RefreshDatabase::class);

test('create rules', function () {
    $provider = new Discord(NotificationChannel::factory()->create([
        'provider' => 'discord',
    ]));

    expect($provider->createRules([]))->toBe([
        'webhook_url' => [
            'required',
            'url',
        ],
    ]);
});

test('create data', function () {
    $provider = new Discord(NotificationChannel::factory()->create([
        'provider' => 'discord',
    ]));

    expect($provider->createData([
        'webhook_url' => 'https://discord.com/xxxxx',
    ]))->toBe([
        'webhook_url' => 'https://discord.com/xxxxx',
    ]);
});

test('data', function () {
    $provider = new Discord(NotificationChannel::factory()->create([
        'provider' => 'discord',
        'data' => [
            'webhook_url' => 'https://discord.com/xxxxx',
        ],
    ]));

    expect($provider->data())->toBe([
        'webhook_url' => 'https://discord.com/xxxxx',
    ]);
});

test('connect', function () {
    $provider = new Discord(NotificationChannel::factory()->create([
        'provider' => 'discord',
        'data' => [
            'webhook_url' => 'https://discord.com/xxxxx',
        ],
    ]));

    Http::fake();

    expect($provider->connect())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://discord.com/xxxxx';
    });
});

test('send', function () {
    $channel = NotificationChannel::factory()->create([
        'provider' => 'discord',
        'data' => [
            'webhook_url' => 'https://discord.com/xxxxx',
        ],
    ]);
    $provider = new Discord($channel);

    Http::fake();

    $provider->send($channel, new TestNotification);

    Http::assertSent(function (Request $request) {
        return $request->body() === '{"content":"Hello"}';
    });
});
