<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ServiceStatus extends Enum
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
}
