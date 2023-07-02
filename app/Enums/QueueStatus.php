<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class QueueStatus extends Enum
{
    const RUNNING = 'running';

    const CREATING = 'creating';

    const DELETING = 'deleting';

    const FAILED = 'failed';

    const STARTING = 'starting';

    const STOPPING = 'stopping';

    const RESTARTING = 'restarting';

    const STOPPED = 'stopped';
}
