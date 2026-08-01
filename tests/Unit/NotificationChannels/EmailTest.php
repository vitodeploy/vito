<?php

use App\Models\NotificationChannel;
use App\NotificationChannels\Email;
use App\NotificationChannels\Email\NotificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Unit\NotificationChannels\TestNotification;

uses(RefreshDatabase::class);

test('create rules', function () {
    $provider = new Email(NotificationChannel::factory()->create([
        'provider' => 'email',
    ]));

    expect($provider->createRules([]))->toBe([
        'email' => [
            'required',
            'email',
        ],
    ]);
});

test('create data', function () {
    $provider = new Email(NotificationChannel::factory()->create([
        'provider' => 'email',
    ]));

    expect($provider->createData([
        'email' => 'user@example.com',
    ]))->toBe([
        'email' => 'user@example.com',
    ]);
});

test('data', function () {
    $provider = new Email(NotificationChannel::factory()->create([
        'provider' => 'email',
        'data' => [
            'email' => 'user@example.com',
        ],
    ]));

    expect($provider->data())->toBe([
        'email' => 'user@example.com',
    ]);
});

test('connect', function () {
    $provider = new Email(NotificationChannel::factory()->create([
        'provider' => 'email',
        'data' => [
            'email' => 'user@example.com',
        ],
    ]));

    Mail::fake();

    expect($provider->connect())->toBeTrue();

    Mail::assertSent(NotificationMail::class);
});

test('send', function () {
    $channel = NotificationChannel::factory()->create([
        'provider' => 'email',
        'data' => [
            'email' => 'user@example.com',
        ],
    ]);
    $provider = new Email($channel);

    Mail::fake();

    $provider->send($channel, new TestNotification);

    Mail::assertSent(NotificationMail::class);
});
