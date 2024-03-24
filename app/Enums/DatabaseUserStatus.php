<?php

namespace App\Enums;

final class DatabaseUserStatus
{
    const READY = 'ready';

    const CREATING = 'creating';

    const FAILED = 'failed';

    const DELETING = 'deleting';
}
