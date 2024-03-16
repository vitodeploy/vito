<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Database extends Enum
{
    const NONE = 'none';

    const MYSQL57 = 'mysql57';

    const MYSQL80 = 'mysql80';

    const MARIADB = 'mariadb';

    const POSTGRESQL12 = 'postgresql12';

    const POSTGRESQL13 = 'postgresql13';

    const POSTGRESQL14 = 'postgresql14';

    const POSTGRESQL15 = 'postgresql15';

    const POSTGRESQL16 = 'postgresql16';
}
