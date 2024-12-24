<?php

namespace App\Enums;

use App\Traits\Enum;

final class Database
{
    use Enum;

    const NONE = 'none';

    const MYSQL57 = 'mysql57';

    const MYSQL80 = 'mysql80';

    const MYSQL84 = 'mysql84';

    const MARIADB103 = 'mariadb103';

    const MARIADB104 = 'mariadb104';

    const MARIADB106 = 'mariadb1006';

    const MARIADB1011 = 'mariadb1011';

    const MARIADB114 = 'mariadb114';

    const POSTGRESQL12 = 'postgresql12';

    const POSTGRESQL13 = 'postgresql13';

    const POSTGRESQL14 = 'postgresql14';

    const POSTGRESQL15 = 'postgresql15';

    const POSTGRESQL16 = 'postgresql16';
}
