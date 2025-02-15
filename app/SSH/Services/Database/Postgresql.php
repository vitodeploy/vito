<?php

namespace App\SSH\Services\Database;

class Postgresql extends AbstractDatabase
{
    protected array $systemDbs = ['template0', 'template1', 'postgres'];

    protected string $defaultCharset = 'UTF8';
}
