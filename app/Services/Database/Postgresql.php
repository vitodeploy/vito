<?php

namespace App\Services\Database;

use App\DTOs\ServiceLog;
use App\Exceptions\SSHError;
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

    /**
     * Reconcile each linked database instead of rendering the per-user link
     * script, so every role keeps the correct access to objects created by any
     * other linked user (PostgreSQL default privileges are keyed by the
     * creating role).
     *
     * @param  array<string>  $databases
     *
     * @throws SSHError
     */
    public function link(string $username, string $host, array $databases, string $permission = 'admin'): void
    {
        foreach ($databases as $database) {
            $this->reconcilePrivileges($database);
        }
    }

    /**
     * @throws SSHError
     */
    public function unlink(string $username, string $host): void
    {
        foreach ($this->userDatabases($username, $host) as $database) {
            $this->reconcilePrivileges($database, [$username]);
        }
    }

    /**
     * @throws SSHError
     */
    public function deleteUser(string $username, string $host): void
    {
        foreach ($this->userDatabases($username, $host) as $database) {
            $this->reconcilePrivileges($database, [$username]);
        }

        parent::deleteUser($username, $host);
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

    /**
     * Render the database-wide reconcile script for every user linked to a
     * database, granting each their role's privileges and wiring up the
     * cross-creator default privileges that keep multiple admins in sync.
     *
     * @param  array<int, string>  $revokeOnly  Usernames to scrub but not grant (e.g. a user being unlinked or deleted).
     *
     * @throws SSHError
     */
    private function reconcilePrivileges(string $database, array $revokeOnly = []): void
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/', $database) !== 1) {
            return;
        }

        $revokeOnly = array_values(array_filter(
            $revokeOnly,
            fn (string $username): bool => preg_match('/^[A-Za-z0-9_-]+$/', $username) === 1
        ));

        $roster = $this->service->server->databaseUsers()
            ->get()
            ->filter(fn (DatabaseUser $user): bool => in_array($database, (array) $user->databases, true))
            ->filter(fn (DatabaseUser $user): bool => ! in_array($user->username, $revokeOnly, true))
            ->filter(fn (DatabaseUser $user): bool => preg_match('/^[A-Za-z0-9_-]+$/', $user->username) === 1)
            ->map(fn (DatabaseUser $user): array => [
                'username' => $user->username,
                'permission' => $user->permission->value,
            ])
            ->values()
            ->all();

        if ($roster === [] && $revokeOnly === []) {
            return;
        }

        $usernames = array_column($roster, 'username');

        $grantCreators = array_values(array_unique(array_merge(
            ['postgres'],
            array_column(
                array_filter($roster, fn (array $user): bool => in_array($user['permission'], ['admin', 'write'], true)),
                'username'
            )
        )));

        $scrubUsers = array_values(array_unique(array_merge($usernames, $revokeOnly)));

        $revokeCreators = array_values(array_unique(array_merge(['postgres'], $usernames, $revokeOnly)));

        $this->service->server->ssh()->exec(
            view($this->getScriptView('reconcile'), [
                'database' => $database,
                'version' => $this->service->version,
                'users' => $roster,
                'grantCreators' => $grantCreators,
                'revokeCreators' => $revokeCreators,
                'scrubUsers' => $scrubUsers,
            ]),
            'sync-database-privileges'
        );
    }

    /**
     * @return array<int, string>
     */
    private function userDatabases(string $username, string $host): array
    {
        $user = $this->service->server->databaseUsers()
            ->where('username', $username)
            ->where('host', $host)
            ->first();

        return $user === null ? [] : (array) $user->databases;
    }
}
