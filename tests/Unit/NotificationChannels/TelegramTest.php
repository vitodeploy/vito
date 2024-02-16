<?php

namespace Tests\Unit\NotificationChannels;

use App\Models\NotificationChannel;
use App\NotificationChannels\Telegram;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramTest extends TestCase
{
    public function test_create_rules(): void
    {
        $provider = new Telegram(NotificationChannel::factory()->create([
            'provider' => 'telegram',
        ]));

        $this->assertSame([
            'bot_token' => 'required|string',
            'chat_id' => 'required',
        ], $provider->createRules([]));
    }

    public function test_create_data(): void
    {
        $provider = new Telegram(NotificationChannel::factory()->create([
            'provider' => 'telegram',
        ]));

        $this->assertSame([
            'bot_token' => 'xxxxx',
            'chat_id' => '12345',
        ], $provider->createData([
            'bot_token' => 'xxxxx',
            'chat_id' => '12345',
        ]));
    }

    public function test_data(): void
    {
        $provider = new Telegram(NotificationChannel::factory()->create([
            'provider' => 'telegram',
            'data' => [
                'bot_token' => 'xxxxx',
                'chat_id' => '12345',
            ],
        ]));

        $this->assertSame([
            'bot_token' => 'xxxxx',
            'chat_id' => '12345',
        ], $provider->data());
    }

    public function test_connect(): void
    {
        $provider = new Telegram(NotificationChannel::factory()->create([
            'provider' => 'telegram',
            'data' => [
                'bot_token' => 'xxxxx',
                'chat_id' => '12345',
            ],
        ]));

        Http::fake();

        $this->assertTrue($provider->connect());

        Http::assertSent(function ($request) {
            if ($request->url() === 'https://api.telegram.org/botxxxxx/sendMessage') {
                return $request->data() === [
                    'chat_id' => '12345',
                    'text' => 'Connected!',
                    'parse_mode' => 'markdown',
                    'disable_web_page_preview' => true,
                ];
            }
        });
    }

    public function test_send(): void
    {
        $channel = NotificationChannel::factory()->create([
            'provider' => 'telegram',
            'data' => [
                'bot_token' => 'xxxxx',
                'chat_id' => '12345',
            ],
        ]);
        $provider = new Telegram($channel);

        Http::fake();

        $provider->send($channel, new TestNotification());

        Http::assertSent(function (Request $request) {
            if ($request->url() === 'https://api.telegram.org/botxxxxx/sendMessage') {
                return $request->data() === [
                    'chat_id' => '12345',
                    'text' => 'Hello',
                    'parse_mode' => 'markdown',
                    'disable_web_page_preview' => true,
                ];
            }
        });
    }
}
