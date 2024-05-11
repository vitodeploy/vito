<?php

namespace App\Enums;

final class CronjobStatus
{
    const CREATING = 'creating';

    const READY = 'ready';

    const DELETING = 'deleting';

    const ENABLING = 'enabling';

    const DISABLING = 'disabling';

    const DISABLED = 'disabled';
}
