<?php

namespace App\ServerFeatures\Motd;

use App\Traits\Enum;

final class MotdPosition
{
    use Enum;

    const START = 'start';

    const END = 'end';

    const REPLACE = 'replace';
}
