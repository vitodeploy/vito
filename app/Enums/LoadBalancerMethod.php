<?php

namespace App\Enums;

use App\Traits\Enum;

final class LoadBalancerMethod
{
    use Enum;

    const ROUND_ROBIN = 'round-robin';

    const LEAST_CONNECTIONS = 'least-connections';

    const IP_HASH = 'ip-hash';
}
