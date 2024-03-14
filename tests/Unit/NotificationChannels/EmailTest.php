<?php

namespace Tests\Unit\NotificationChannels;

use App\Models\NotificationChannel;
use App\NotificationChannels\Email;
use App\NotificationChannels\Email\NotificationMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTest extends TestCase
{
    public function test_create_rules(): void
    {
        $provider = new Email(NotificationChannel::factory()->create([
            'provider' => 'email',
        ]));

        $this->assertSame([
            'email' => 'required|email',
        ], $provider->createRules([]));
    }

    public function test_create_data(): void
    {
        $provider = new Email(NotificationChannel::factory()->create([
            'provider' => 'email',
        ]));

        $this->assertSame([
            'email' => 'user@example.com',
        ], $provider->createData([
            'email' => 'user@example.com',
        ]));
    }

    public function test_data(): void
    {
        $provider = new Email(NotificationChannel::factory()->create([
            'provider' => 'email',
            'data' => [
                'email' => 'user@example.com',
            ],
        ]));

        $this->assertSame([
            'email' => 'user@example.com',
        ], $provider->data());
    }

    public function test_connect(): void
    {
        $provider = new Email(NotificationChannel::factory()->create([
            'provider' => 'email',
            'data' => [
                'email' => 'user@example.com',
            ],
        ]));

        Mail::fake();

        $this->assertTrue($provider->connect());

        Mail::assertSent(NotificationMail::class);
    }

    public function test_send(): void
    {
        $channel = NotificationChannel::factory()->create([
            'provider' => 'email',
            'data' => [
                'email' => 'user@example.com',
            ],
        ]);
        $provider = new Email($channel);

        Mail::fake();

        $provider->send($channel, new TestNotification());

        Mail::assertSent(NotificationMail::class);
    }
}
