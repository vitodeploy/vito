<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ServerProvider extends Enum
{
    const CUSTOM = 'custom';

    const AWS = 'aws';

    const LINODE = 'linode';

    const DIGITALOCEAN = 'digitalocean';

    const VULTR = 'vultr';

    const HETZNER = 'hetzner';
}
