<?php

namespace App\Enums;

use App\Contracts\VitoEnum;
use Forjed\InertiaTable\Contracts\HasTableDisplay;

enum DeploymentStage: string implements HasTableDisplay, VitoEnum
{
    case CLONE = 'clone';
    case BUILD = 'build';
    case PRE_RELEASE = 'pre-release';
    case RELEASE = 'release';
    case POST_RELEASE = 'post-release';

    public function next(): ?self
    {
        return match ($this) {
            self::CLONE => self::BUILD,
            self::BUILD => self::PRE_RELEASE,
            self::PRE_RELEASE => self::RELEASE,
            self::RELEASE => self::POST_RELEASE,
            self::POST_RELEASE => null,
        };
    }

    public function getText(): string
    {
        return $this === self::PRE_RELEASE ? 'Release command' : $this->value;
    }

    public function getColor(): string
    {
        return 'info';
    }
}
