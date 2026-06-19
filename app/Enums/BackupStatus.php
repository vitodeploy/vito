<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum BackupStatus: string implements VitoEnum
{
    case DELETING = 'deleting';

    public function getColor(): string
    {
        return 'warning';
    }

    public function getText(): string
    {
        return $this->value;
    }
}
