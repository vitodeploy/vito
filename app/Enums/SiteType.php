<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SiteType extends Enum
{
    const PHP = 'php';

    const PHP_BLANK = 'php-blank';

    const LARAVEL = 'laravel';

    const WORDPRESS = 'wordpress';
}
