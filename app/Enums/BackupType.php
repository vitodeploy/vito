<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum BackupType: string implements VitoEnum
{
    case DATABASE = 'database';
    case FILE = 'file';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return $this->value;
    }
}
