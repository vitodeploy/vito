<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class StorageProvider extends Enum
{
    const GOOGLE = 'google';

    const DROPBOX = 'dropbox';
}
