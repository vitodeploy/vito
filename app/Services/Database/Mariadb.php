<?php

namespace App\Services\Database;

use App\DTOs\ServiceLog;
use App\Services\HasLogs;
use App\Services\SupportsNetworking;
use Illuminate\Contracts\View\View;

class Mariadb extends AbstractDatabase implements HasLogs, SupportsNetworking
{
    use ManagesMysqlNetworking;

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

    protected function installScript(): View
    {
        return view($this->getScriptView('install'), [
            'version' => $this->service->version,
        ]);
    }

    public function versionCommand(): ?string
    {
        return 'mariadb --version | grep -oE \'[0-9]+\.[0-9]+\.[0-9]+\' | head -n 1';
    }

    protected function networkingManagesXPlugin(): bool
    {
        return false;
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
