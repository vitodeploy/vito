<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DatabaseUserStatus extends Enum
{
    const READY = 'ready';

    const CREATING = 'creating';

    const FAILED = 'failed';

    const DELETING = 'deleting';
}
