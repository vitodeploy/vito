<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SshKeyStatus extends Enum
{
    const ADDING = 'adding';

    const ADDED = 'added';

    const DELETING = 'deleting';
}
