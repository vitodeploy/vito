<?php

namespace App\Enums;

final class BackupFileStatus
{
    const CREATED = 'created';

    const CREATING = 'creating';

    const FAILED = 'failed';

    const DELETING = 'deleting';

    const RESTORING = 'restoring';

    const RESTORED = 'restored';

    const RESTORE_FAILED = 'restore_failed';
}
