<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DeploymentStatus extends Enum
{
    const DEPLOYING = 'deploying';

    const FINISHED = 'finished';

    const FAILED = 'failed';
}
