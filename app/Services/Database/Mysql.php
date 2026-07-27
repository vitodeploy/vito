<?php

namespace App\Services\Database;

use App\DTOs\ServiceLog;
use App\Services\HasLogs;
use App\Services\SupportsNetworking;
use Illuminate\Contracts\View\View;

class Mysql extends AbstractDatabase implements HasLogs, SupportsNetworking
{
    use ManagesMysqlNetworking;

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

    protected function installScript(): View
    {
        return view($this->getScriptView('install'), [
            'version' => $this->service->version,
        ]);
    }

    public function versionCommand(): ?string
    {
        return 'mysql -V | grep -oE \'[0-9]+\.[0-9]+\.[0-9]+\' | head -n 1';
    }

    protected function networkingManagesXPlugin(): bool
    {
        return true;
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
