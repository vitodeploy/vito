<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SiteFeature extends Enum
{
    const DEPLOYMENT = 'deployment';

    const ENV = 'env';

    const SSL = 'ssl';

    const QUEUES = 'queues';
}
