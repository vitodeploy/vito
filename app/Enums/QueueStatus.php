<?php

namespace App\Enums;

final class QueueStatus
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
