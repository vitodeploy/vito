<?php

namespace App\Enums;

final class ServiceStatus
{
    const string READY = 'ready';

    const string INSTALLING = 'installing';

    const string INSTALLATION_FAILED = 'installation_failed';

    const string UNINSTALLING = 'uninstalling';

    const string FAILED = 'failed';

    const string STARTING = 'starting';

    const string STOPPING = 'stopping';

    const string RESTARTING = 'restarting';

    const string STOPPED = 'stopped';

    const string ENABLING = 'enabling';

    const string DISABLING = 'disabling';

    const string DISABLED = 'disabled';
}
