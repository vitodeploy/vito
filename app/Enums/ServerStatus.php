<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ServerStatus extends Enum
{
    const READY = 'ready';

    const INSTALLING = 'installing';

    const INSTALLATION_FAILED = 'installation_failed';

    const DISCONNECTED = 'disconnected';
}
