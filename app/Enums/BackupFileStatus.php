<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class BackupFileStatus extends Enum
{
    const CREATED = 'created';

    const CREATING = 'creating';

    const FAILED = 'failed';

    const DELETING = 'deleting';

    const RESTORING = 'restoring';

    const RESTORED = 'restored';

    const RESTORE_FAILED = 'restore_failed';
}
