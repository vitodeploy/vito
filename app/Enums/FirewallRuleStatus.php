<?php

namespace App\Enums;

final class FirewallRuleStatus
{
    const string CREATING = 'creating';

    const string UPDATING = 'updating';

    const string READY = 'ready';

    const string DELETING = 'deleting';

    const string FAILED = 'failed';
}
