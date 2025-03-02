<?php

namespace App\SSH\Services\Database;

class Mysql extends AbstractDatabase
{
    protected array $systemDbs = ['information_schema', 'performance_schema', 'mysql', 'sys'];

    protected string $defaultCharset = 'utf8mb3';
}
