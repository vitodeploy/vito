<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FirewallRuleStatus extends Enum
{
    const CREATING = 'creating';

    const READY = 'ready';

    const DELETING = 'deleting';
}
