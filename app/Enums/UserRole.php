<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum UserRole: string implements VitoEnum
{
    case USER = 'user';
    case ADMIN = 'admin';
    case OWNER = 'owner';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return $this->value;
    }
}
