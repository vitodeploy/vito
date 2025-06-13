<?php

namespace App\Enums;

final class BackupFileStatus
{
    const string CREATED = 'created';

    const string CREATING = 'creating';

    const string FAILED = 'failed';

    const string DELETING = 'deleting';

    const string RESTORING = 'restoring';

    const string RESTORED = 'restored';

    const string RESTORE_FAILED = 'restore_failed';
}
