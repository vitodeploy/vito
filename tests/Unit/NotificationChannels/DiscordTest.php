<?php

namespace Tests\Unit\NotificationChannels;

use App\Models\NotificationChannel;
use App\NotificationChannels\Discord;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordTest extends TestCase
{
    public function test_create_rules(): void
    {
        $provider = new Discord(NotificationChannel::factory()->create([
            'provider' => 'discord',
        ]));

        $this->assertSame([
            'webhook_url' => 'required|url',
        ], $provider->createRules([]));
    }

    public function test_create_data(): void
    {
        $provider = new Discord(NotificationChannel::factory()->create([
            'provider' => 'discord',
        ]));

        $this->assertSame([
            'webhook_url' => 'https://discord.com/xxxxx',
        ], $provider->createData([
            'webhook_url' => 'https://discord.com/xxxxx',
        ]));
    }

    public function test_data(): void
    {
        $provider = new Discord(NotificationChannel::factory()->create([
            'provider' => 'discord',
            'data' => [
                'webhook_url' => 'https://discord.com/xxxxx',
            ],
        ]));

        $this->assertSame([
            'webhook_url' => 'https://discord.com/xxxxx',
        ], $provider->data());
    }

    public function test_connect(): void
    {
        $provider = new Discord(NotificationChannel::factory()->create([
            'provider' => 'discord',
            'data' => [
                'webhook_url' => 'https://discord.com/xxxxx',
            ],
        ]));

        Http::fake();

        $this->assertTrue($provider->connect());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.com/xxxxx';
        });
    }

    public function test_send(): void
    {
        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'data' => [
                'webhook_url' => 'https://discord.com/xxxxx',
            ],
        ]);
        $provider = new Discord($channel);

        Http::fake();

        $provider->send($channel, new TestNotification());

        Http::assertSent(function (Request $request) {
            return $request->body() === '{"content":"Hello"}';
        });
    }
}
