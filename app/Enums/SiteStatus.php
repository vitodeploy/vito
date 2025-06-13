<?php

namespace App\Enums;

final class SiteStatus
{
    const string READY = 'ready';

    const string INSTALLING = 'installing';

    const string INSTALLATION_FAILED = 'installation_failed';

    const string DELETING = 'deleting';
}
