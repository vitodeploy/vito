<?php

namespace App\Enums;

final class ServerStatus
{
    const string READY = 'ready';

    const string INSTALLING = 'installing';

    const string INSTALLATION_FAILED = 'installation_failed';

    const string DISCONNECTED = 'disconnected';

    const string UPDATING = 'updating';
}
