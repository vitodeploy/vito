<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SourceControl extends Enum
{
    const GITHUB = 'github';

    const GITLAB = 'gitlab';

    const BITBUCKET = 'bitbucket';

    const CUSTOM = 'custom';
}
