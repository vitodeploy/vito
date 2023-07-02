<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SslStatus extends Enum
{
    const CREATED = 'created';

    const CREATING = 'creating';

    const DELETING = 'deleting';

    const FAILED = 'failed';
}
