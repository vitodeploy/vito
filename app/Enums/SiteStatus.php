<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SiteStatus extends Enum
{
    const READY = 'ready';

    const INSTALLING = 'installing';

    const INSTALLATION_FAILED = 'installation_failed';

    const DELETING = 'deleting';
}
