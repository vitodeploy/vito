<?php

namespace App\Enums;

final class DatabaseUserStatus
{
    const string READY = 'ready';

    const string CREATING = 'creating';

    const string FAILED = 'failed';

    const string DELETING = 'deleting';
}
