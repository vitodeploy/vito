<?php

namespace App\SSH\Services\Database;

class Postgresql extends AbstractDatabase
{
    protected function getScriptsDir(): string
    {
        return 'postgresql';
    }
}
