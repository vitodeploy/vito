<?php

namespace App\Services\Database;

class Mariadb extends AbstractDatabase
{
    protected array $systemDbs = ['information_schema', 'performance_schema', 'mysql', 'sys'];

    protected array $systemUsers = [
        'root',
        'mysql',
        'mariadb.sys',
    ];

    protected string $defaultCharset = 'utf8mb3';

    public static function id(): string
    {
        return 'mariadb';
    }

    public static function type(): string
    {
        return 'database';
    }

    public function unit(): string
    {
        return 'mariadb';
    }
}
