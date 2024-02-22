<?php

namespace Tests\Unit\NotificationChannels;

use App\Notifications\AbstractNotification;

class TestNotification extends AbstractNotification
{
    public function rawText(): string
    {
        return 'Hello';
    }
}
