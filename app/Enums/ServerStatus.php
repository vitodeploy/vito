<?php

namespace App\Enums;

final class ServerStatus
{
    const READY = 'ready';

    const INSTALLING = 'installing';

    const INSTALLATION_FAILED = 'installation_failed';

    const DISCONNECTED = 'disconnected';

    const UPDATING = 'updating';
}
