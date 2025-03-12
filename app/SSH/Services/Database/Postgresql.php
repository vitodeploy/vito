<?php

namespace App\SSH\Services\Database;

class Postgresql extends AbstractDatabase
{
    protected array $systemDbs = ['template0', 'template1', 'postgres'];

    /**
     * @var string[]
     */
    protected array $systemUsers = ['postgres'];

    protected string $defaultCharset = 'UTF8';

    protected int $headerLines = 2;

    protected string $separator = '|';

    protected bool $removeLastRow = true;
}
