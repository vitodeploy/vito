<?php

namespace App\Enums;

final class ServiceStatus
{
    const READY = 'ready';

    const INSTALLING = 'installing';

    const INSTALLATION_FAILED = 'installation_failed';

    const UNINSTALLING = 'uninstalling';

    const FAILED = 'failed';

    const STARTING = 'starting';

    const STOPPING = 'stopping';

    const RESTARTING = 'restarting';

    const STOPPED = 'stopped';

    const ENABLING = 'enabling';

    const DISABLING = 'disabling';

    const DISABLED = 'disabled';
}
