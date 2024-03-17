<?php

namespace App\Enums;

final class SiteStatus
{
    const READY = 'ready';

    const INSTALLING = 'installing';

    const INSTALLATION_FAILED = 'installation_failed';

    const DELETING = 'deleting';
}
