<?php

namespace App\Services\Database;

use App\DTOs\ServiceLog;
use App\Services\HasLogs;

class Mariadb extends AbstractDatabase implements HasLogs
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

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'mariadb --version | grep -oE \'[0-9]+\.[0-9]+\.[0-9]+\' | head -n 1'
        );

        return trim($version);
    }

    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'mariadb:journal',
                serviceLabel: 'MariaDB',
                label: 'Service journal',
                source: ServiceLog::SOURCE_JOURNAL,
                target: 'mariadb.service',
            ),
        ];
    }
}
