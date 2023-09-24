<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class StorageProvider extends Enum
{
    const DROPBOX = 'dropbox';

    const FTP = 'ftp';
}
