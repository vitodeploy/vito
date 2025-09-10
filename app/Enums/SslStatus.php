<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum SslStatus: string implements VitoEnum
{
    case CREATED = 'created';
    case CREATING = 'creating';
    case DELETING = 'deleting';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::CREATED => 'success',
            self::CREATING,
            self::DELETING => 'warning',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
