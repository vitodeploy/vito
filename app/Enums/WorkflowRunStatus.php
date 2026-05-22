<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum WorkflowRunStatus: string implements HasTableDisplay, VitoEnum
{
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::RUNNING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
