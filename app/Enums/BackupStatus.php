<?php

namespace App\Enums;

final class BackupStatus
{
    const string RUNNING = 'running';

    const string FAILED = 'failed';

    const string DELETING = 'deleting';

    const string STOPPED = 'stopped';
}
