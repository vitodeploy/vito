<?php

namespace App\Enums;

final class WorkerStatus
{
    const string RUNNING = 'running';

    const string CREATING = 'creating';

    const string DELETING = 'deleting';

    const string FAILED = 'failed';

    const string STARTING = 'starting';

    const string STOPPING = 'stopping';

    const string RESTARTING = 'restarting';

    const string STOPPED = 'stopped';
}
