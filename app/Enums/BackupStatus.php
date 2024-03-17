<?php

namespace App\Enums;

final class BackupStatus
{
    const READY = 'ready';

    const RUNNING = 'running';

    const FAILED = 'failed';

    const DELETING = 'deleting';
}
