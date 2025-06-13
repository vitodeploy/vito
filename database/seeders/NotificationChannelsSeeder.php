<?php

namespace Database\Seeders;

use App\Models\NotificationChannel;
use App\NotificationChannels\Discord;
use App\NotificationChannels\Email;
use App\NotificationChannels\Slack;
use App\NotificationChannels\Telegram;
use Illuminate\Database\Seeder;

class NotificationChannelsSeeder extends Seeder
{
    public function run(): void
    {
        NotificationChannel::factory()->create([
            'label' => 'Slack',
            'provider' => Slack::id(),
            'data' => [
                'webhook' => 'slack_webhook',
            ],
            'connected' => 1,
        ]);

        NotificationChannel::factory()->create([
            'label' => 'Discord',
            'provider' => Discord::id(),
            'data' => [
                'webhook' => 'discord_webhook',
            ],
            'connected' => 1,
        ]);

        NotificationChannel::factory()->create([
            'label' => 'Telegram',
            'provider' => Telegram::id(),
            'data' => [
                'token' => 'telegram_token',
                'chat_id' => 'telegram_chat_id',
            ],
            'connected' => 1,
        ]);

        NotificationChannel::factory()->create([
            'label' => 'Email',
            'provider' => Email::id(),
            'data' => [
                'email' => 'email@vitodeploy.com',
            ],
            'connected' => 1,
        ]);
    }
}
