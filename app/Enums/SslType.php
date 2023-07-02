<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SslType extends Enum
{
    const LETSENCRYPT = 'letsencrypt';

    const CUSTOM = 'custom';
}
