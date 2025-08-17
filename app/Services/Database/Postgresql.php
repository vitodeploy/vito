<?php

namespace App\Services\Database;

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

    public static function id(): string
    {
        return 'postgresql';
    }

    public static function type(): string
    {
        return 'database';
    }

    public function unit(): string
    {
        return 'postgresql';
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'psql --version | grep -oE \'[0-9]+\.[0-9]+(\.[0-9]+)?\' | head -n 1'
        );

        return trim($version);
    }
}
