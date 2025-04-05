<?php

namespace App\SSH\Services\Database;

class Mariadb extends AbstractDatabase
{
    protected array $systemDbs = ['information_schema', 'performance_schema', 'mysql', 'sys'];

    protected array $systemUsers = [
        'root',
        'mysql',
        'mariadb.sys',
    ];

    protected string $defaultCharset = 'utf8mb3';
}
