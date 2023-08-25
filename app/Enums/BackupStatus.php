<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class BackupStatus extends Enum
{
    const READY = 'ready';

    const RUNNING = 'running';

    const FAILED = 'failed';

    const DELETING = 'deleting';
}
