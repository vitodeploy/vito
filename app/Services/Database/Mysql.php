<?php

namespace App\Services\Database;

use App\DTOs\ServiceLog;
use App\Services\HasLogs;

class Mysql extends AbstractDatabase implements HasLogs
{
    protected array $systemDbs = ['information_schema', 'performance_schema', 'mysql', 'sys'];

    protected array $systemUsers = [
        'root',
        'mysql.session',
        'mysql.sys',
        'mysql.infoschema',
    ];

    protected string $defaultCharset = 'utf8mb3';

    public static function id(): string
    {
        return 'mysql';
    }

    public static function type(): string
    {
        return 'database';
    }

    public function unit(): string
    {
        return 'mysql';
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'mysql -V | grep -oE \'[0-9]+\.[0-9]+\.[0-9]+\''
        );

        return trim($version);
    }

    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'mysql:journal',
                serviceLabel: 'MySQL',
                label: 'Service journal',
                source: ServiceLog::SOURCE_JOURNAL,
                target: 'mysql.service',
            ),
        ];
    }
}
