<?php

use App\Models\NotificationChannel;
use App\NotificationChannels\Slack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Unit\NotificationChannels\TestNotification;

uses(RefreshDatabase::class);

test('create rules', function () {
    $provider = new Slack(NotificationChannel::factory()->create([
        'provider' => 'slack',
    ]));

    expect($provider->createRules([]))->toBe([
        'webhook_url' => [
            'required',
            'url',
        ],
    ]);
});

test('create data', function () {
    $provider = new Slack(NotificationChannel::factory()->create([
        'provider' => 'slack',
    ]));

    expect($provider->createData([
        'webhook_url' => 'https://slack.com/xxxxx',
    ]))->toBe([
        'webhook_url' => 'https://slack.com/xxxxx',
    ]);
});

test('data', function () {
    $provider = new Slack(NotificationChannel::factory()->create([
        'provider' => 'slack',
        'data' => [
            'webhook_url' => 'https://slack.com/xxxxx',
        ],
    ]));

    expect($provider->data())->toBe([
        'webhook_url' => 'https://slack.com/xxxxx',
    ]);
});

test('connect', function () {
    $provider = new Slack(NotificationChannel::factory()->create([
        'provider' => 'slack',
        'data' => [
            'webhook_url' => 'https://slack.com/xxxxx',
        ],
    ]));

    Http::fake();

    expect($provider->connect())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://slack.com/xxxxx';
    });
});

test('send', function () {
    $channel = NotificationChannel::factory()->create([
        'provider' => 'slack',
        'data' => [
            'webhook_url' => 'https://slack.com/xxxxx',
        ],
    ]);
    $provider = new Slack($channel);

    Http::fake();

    $provider->send($channel, new TestNotification);

    Http::assertSent(function (Request $request) {
        return $request->body() === '{"text":"Hello"}';
    });
});
