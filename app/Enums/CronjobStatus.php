<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CronjobStatus extends Enum
{
    const CREATING = 'creating';

    const READY = 'ready';

    const DELETING = 'deleting';
}
