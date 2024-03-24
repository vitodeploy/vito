<?php

namespace App\SSH\Services\Database;

class Mysql extends AbstractDatabase
{
    protected function getScriptsDir(): string
    {
        return 'mysql';
    }
}
