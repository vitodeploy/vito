<?php

namespace Tests\Unit\NotificationChannels;

use App\Models\NotificationChannel;
use App\NotificationChannels\Slack;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlackTest extends TestCase
{
    public function test_create_rules(): void
    {
        $provider = new Slack(NotificationChannel::factory()->create([
            'provider' => 'slack',
        ]));

        $this->assertSame([
            'webhook_url' => 'required|url',
        ], $provider->createRules([]));
    }

    public function test_create_data(): void
    {
        $provider = new Slack(NotificationChannel::factory()->create([
            'provider' => 'slack',
        ]));

        $this->assertSame([
            'webhook_url' => 'https://slack.com/xxxxx',
        ], $provider->createData([
            'webhook_url' => 'https://slack.com/xxxxx',
        ]));
    }

    public function test_data(): void
    {
        $provider = new Slack(NotificationChannel::factory()->create([
            'provider' => 'slack',
            'data' => [
                'webhook_url' => 'https://slack.com/xxxxx',
            ],
        ]));

        $this->assertSame([
            'webhook_url' => 'https://slack.com/xxxxx',
        ], $provider->data());
    }

    public function test_connect(): void
    {
        $provider = new Slack(NotificationChannel::factory()->create([
            'provider' => 'slack',
            'data' => [
                'webhook_url' => 'https://slack.com/xxxxx',
            ],
        ]));

        Http::fake();

        $this->assertTrue($provider->connect());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://slack.com/xxxxx';
        });
    }

    public function test_send(): void
    {
        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'data' => [
                'webhook_url' => 'https://slack.com/xxxxx',
            ],
        ]);
        $provider = new Slack($channel);

        Http::fake();

        $provider->send($channel, new TestNotification());

        Http::assertSent(function (Request $request) {
            return $request->body() === '{"text":"Hello"}';
        });
    }
}
