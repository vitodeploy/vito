<?php

namespace App\NotificationChannels;

use App\Notifications\NotificationInterface;

interface NotificationChannel
{
    public static function id(): string;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createData(array $input): array;

    /**
     * @return array<string, mixed>
     */
    public function data(): array;

    public function connect(): bool;

    public function send(object $notifiable, NotificationInterface $notification): void;
}
