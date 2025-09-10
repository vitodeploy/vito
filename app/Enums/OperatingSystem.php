<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum OperatingSystem: string implements VitoEnum
{
    case UBUNTU18 = 'ubuntu_18';
    case UBUNTU20 = 'ubuntu_20';
    case UBUNTU22 = 'ubuntu_22';
    case UBUNTU24 = 'ubuntu_24';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return $this->value;
    }

    public function getVersion(): string
    {
        return match ($this) {
            self::UBUNTU18 => '18.04',
            self::UBUNTU20 => '20.04',
            self::UBUNTU22 => '22.04',
            self::UBUNTU24 => '24.04',
        };
    }
}
