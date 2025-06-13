<?php

namespace App\Enums;

final class DatabaseStatus
{
    const string READY = 'ready';

    const string CREATING = 'creating';

    const string FAILED = 'failed';

    const string DELETING = 'deleting';
}
