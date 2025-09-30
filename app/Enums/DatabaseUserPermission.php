<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum DatabaseUserPermission: string implements VitoEnum
{
    case READ = 'read';
    case WRITE = 'write';
    case ADMIN = 'admin';

    public function getColor(): string
    {
        return match ($this) {
            self::READ => 'default',
            self::WRITE => 'info',
            self::ADMIN => 'warning',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
