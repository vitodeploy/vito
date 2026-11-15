<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum OperatingSystem: string implements VitoEnum
{
    case UBUNTU18 = 'ubuntu_18';
    case UBUNTU20 = 'ubuntu_20';
    case UBUNTU22 = 'ubuntu_22';
    case UBUNTU24 = 'ubuntu_24';
    case UBUNTU26 = 'ubuntu_26';
    case DEBIAN12 = 'debian_12';
    case DEBIAN13 = 'debian_13';

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
            self::UBUNTU26 => '26.04',
            self::DEBIAN12 => '12',
            self::DEBIAN13 => '13',
        };
    }

    public function getCodename(): string
    {
        return match ($this) {
            self::UBUNTU18 => 'bionic',
            self::UBUNTU20 => 'focal',
            self::UBUNTU22 => 'jammy',
            self::UBUNTU24 => 'noble',
            self::UBUNTU26 => 'resolute',
            self::DEBIAN12 => 'bookworm',
            self::DEBIAN13 => 'trixie',
        };
    }

    public function getDistro(): string
    {
        return $this->isDebian() ? 'Debian' : 'Ubuntu';
    }

    public function isDebian(): bool
    {
        return in_array($this, [self::DEBIAN12, self::DEBIAN13], true);
    }

    public function getLabel(): string
    {
        return $this->getDistro().' '.$this->getVersion();
    }
}
