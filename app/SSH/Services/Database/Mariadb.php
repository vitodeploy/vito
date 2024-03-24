<?php

namespace App\SSH\Services\Database;

class Mariadb extends AbstractDatabase
{
    protected function getScriptsDir(): string
    {
        return 'mysql';
    }
}
