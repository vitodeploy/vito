<?php

namespace App\Services\Database;

use App\DTOs\ServiceLog;
use App\Models\DatabaseUser;
use App\Models\Server;
use App\Services\HasLogs;
use Illuminate\Contracts\View\View;

class Postgresql extends AbstractDatabase implements HasLogs
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

    public function usesHost(): bool
    {
        return false;
    }

    public function databaseUserExists(Server $server, string $username, string $host, ?DatabaseUser $ignore = null): bool
    {
        return $server->databaseUsers()
            ->where('username', $username)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();
    }

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

    protected function installScript(): View
    {
        return view($this->getScriptView('install'), [
            'version' => $this->service->version,
        ]);
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'psql --version | grep -oE \'[0-9]+\.[0-9]+(\.[0-9]+)?\' | head -n 1'
        );

        return trim($version);
    }

    public function logs(): array
    {
        return [
            new ServiceLog(
                key: 'postgresql:journal',
                serviceLabel: 'PostgreSQL',
                label: 'Service journal',
                source: ServiceLog::SOURCE_JOURNAL,
                target: 'postgresql.service',
            ),
        ];
    }
}
