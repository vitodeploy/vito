<?php

namespace App\Enums;

final class CronjobStatus
{
    const string CREATING = 'creating';

    const string READY = 'ready';

    const string DELETING = 'deleting';

    const string ENABLING = 'enabling';

    const string DISABLING = 'disabling';

    const string UPDATING = 'updating';

    const string DISABLED = 'disabled';
}
