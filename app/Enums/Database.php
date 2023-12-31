<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Database extends Enum
{
    const NONE = 'none';

    const MYSQL57 = 'mysql57';

    const MYSQL80 = 'mysql80';

    const MARIADB = 'mariadb';
}
