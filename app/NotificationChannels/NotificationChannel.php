<?php

namespace App\NotificationChannels;

use App\Notifications\NotificationInterface;

interface NotificationChannel
{
    public function createRules(array $input): array;

    public function createData(array $input): array;

    public function data(): array;

    public function connect(): bool;

    public function send(object $notifiable, NotificationInterface $notification): void;
}
