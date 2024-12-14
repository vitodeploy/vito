<?php

namespace Database\Seeders;

use App\Models\NotificationChannel;
use Illuminate\Database\Seeder;

class NotificationChannelsSeeder extends Seeder
{
    public function run(): void
    {
        NotificationChannel::factory()->create([
            'label' => 'Slack',
            'provider' => \App\Enums\NotificationChannel::SLACK,
            'data' => [
                'webhook' => 'slack_webhook',
            ],
            'connected' => 1,
        ]);

        NotificationChannel::factory()->create([
            'label' => 'Discord',
            'provider' => \App\Enums\NotificationChannel::DISCORD,
            'data' => [
                'webhook' => 'discord_webhook',
            ],
            'connected' => 1,
        ]);

        NotificationChannel::factory()->create([
            'label' => 'Telegram',
            'provider' => \App\Enums\NotificationChannel::TELEGRAM,
            'data' => [
                'token' => 'telegram_token',
                'chat_id' => 'telegram_chat_id',
            ],
            'connected' => 1,
        ]);

        NotificationChannel::factory()->create([
            'label' => 'Email',
            'provider' => \App\Enums\NotificationChannel::EMAIL,
            'data' => [
                'email' => 'email@vitodeploy.com',
            ],
            'connected' => 1,
        ]);
    }
}
