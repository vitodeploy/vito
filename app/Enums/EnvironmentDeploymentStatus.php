<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum EnvironmentDeploymentStatus: string implements HasTableDisplay, VitoEnum
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case FINISHED = 'finished';
    case FAILED = 'failed';
    case PARTIAL = 'partial';
    case CANCELLED = 'cancelled';
    case ROLLED_BACK = 'rolled_back';
    case SUPERSEDED = 'superseded';

    public function isTerminal(): bool
    {
        return ! in_array($this, [self::PENDING, self::RUNNING], true);
    }

    public function getText(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FINISHED => 'success',
            self::FAILED, self::PARTIAL => 'danger',
            self::PENDING, self::RUNNING => 'warning',
            default => 'gray',
        };
    }
}
