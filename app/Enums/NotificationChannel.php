<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationChannel extends Enum
{
    const EMAIL = 'email';

    const SLACK = 'slack';

    const DISCORD = 'discord';
}
