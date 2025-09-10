<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ScriptExecutionStatus: string implements VitoEnum
{
    case EXECUTING = 'executing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::EXECUTING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
