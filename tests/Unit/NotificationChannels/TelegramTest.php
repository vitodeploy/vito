<?php

use App\Models\NotificationChannel;
use App\NotificationChannels\Telegram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Unit\NotificationChannels\TestNotification;

uses(RefreshDatabase::class);

test('create rules', function () {
    $provider = new Telegram(NotificationChannel::factory()->create([
        'provider' => 'telegram',
    ]));

    expect($provider->createRules([]))->toBe([
        'bot_token' => [
            'required',
        ],
        'chat_id' => [
            'required',
        ],
    ]);
});

test('create data', function () {
    $provider = new Telegram(NotificationChannel::factory()->create([
        'provider' => 'telegram',
    ]));

    expect($provider->createData([
        'bot_token' => 'xxxxx',
        'chat_id' => '12345',
    ]))->toBe([
        'bot_token' => 'xxxxx',
        'chat_id' => '12345',
    ]);
});

test('data', function () {
    $provider = new Telegram(NotificationChannel::factory()->create([
        'provider' => 'telegram',
        'data' => [
            'bot_token' => 'xxxxx',
            'chat_id' => '12345',
        ],
    ]));

    expect($provider->data())->toBe([
        'bot_token' => 'xxxxx',
        'chat_id' => '12345',
    ]);
});

test('connect', function () {
    $provider = new Telegram(NotificationChannel::factory()->create([
        'provider' => 'telegram',
        'data' => [
            'bot_token' => 'xxxxx',
            'chat_id' => '12345',
        ],
    ]));

    Http::fake();

    expect($provider->connect())->toBeTrue();

    Http::assertSent(function ($request) {
        if ($request->url() === 'https://api.telegram.org/botxxxxx/sendMessage') {
            return $request->data() === [
                'chat_id' => '12345',
                'text' => 'Connected!',
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];
        }
    });
});

test('send', function () {
    $channel = NotificationChannel::factory()->create([
        'provider' => 'telegram',
        'data' => [
            'bot_token' => 'xxxxx',
            'chat_id' => '12345',
        ],
    ]);
    $provider = new Telegram($channel);

    Http::fake();

    $provider->send($channel, new TestNotification);

    Http::assertSent(function (Request $request) {
        if ($request->url() === 'https://api.telegram.org/botxxxxx/sendMessage') {
            return $request->data() === [
                'chat_id' => '12345',
                'text' => 'Hello',
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];
        }
    });
});
