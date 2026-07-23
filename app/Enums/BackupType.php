<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum BackupType: string implements HasTableDisplay, VitoEnum
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
