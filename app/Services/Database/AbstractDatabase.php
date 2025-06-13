<?php

namespace App\Services\Database;

use App\Actions\Database\SyncDatabases;
use App\Enums\BackupStatus;
use App\Exceptions\ServiceInstallationFailed;
use App\Exceptions\SSHError;
use App\Models\BackupFile;
use App\Services\AbstractService;
use Closure;

abstract class AbstractDatabase extends AbstractService implements Database
{
    /**
     * @var array<string>
     */
    protected array $systemDbs = [];

    /**
     * @var array<string>
     */
    protected array $systemUsers = [];

    protected string $defaultCharset;

    protected string $separator = "\t";

    protected int $headerLines = 1;

    protected bool $removeLastRow = false;

    /**
     * @phpstan-return view-string
     */
    protected function getScriptView(string $script): string
    {
        /** @phpstan-ignore-next-line */
        return 'ssh.services.database.'.$this->service->name.'.'.$script;
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $databaseExists = $this->service->server->database();
                    if ($databaseExists) {
                        $fail('You already have a database service on the server.');
                    }
                },
            ],
        ];
    }

    /**
     * @throws ServiceInstallationFailed
     * @throws SSHError
     */
    public function install(): void
    {
        $version = str_replace('.', '', $this->service->version);
        $command = view($this->getScriptView('install-'.$version));
        $this->service->server->ssh()->exec($command, 'install-'.$this->service->name.'-'.$version);
        $status = $this->service->server->systemd()->status($this->unit());
        $this->service->validateInstall($status);
        $this->service->server->os()->cleanup();
        /** @TODO implement post-install for services and move it there */
        app(SyncDatabases::class)->sync($this->service->server);
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $hasDatabase = $this->service->server->databases()->exists();
                    if ($hasDatabase) {
                        $fail('You have database(s) on the server.');
                    }
                    $hasDatabaseUser = $this->service->server->databaseUsers()->exists();
                    if ($hasDatabaseUser) {
                        $fail('You have database user(s) on the server.');
                    }
                    $hasRunningBackup = $this->service->server->backups()
                        ->where('status', BackupStatus::RUNNING)
                        ->exists();
                    if ($hasRunningBackup) {
                        $fail('You have database backup(s) on the server.');
                    }
                },
            ],
        ];
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $version = $this->service->version;
        $command = view($this->getScriptView('uninstall'));
        $this->service->server->ssh()->exec($command, 'uninstall-'.$this->service->name.'-'.$version);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function create(string $name, string $charset, string $collation): void
    {
        $this->service->server->ssh()->exec(
            view($this->getScriptView('create'), [
                'name' => $name,
                'charset' => $charset,
                'collation' => $collation,
            ]),
            'create-database'
        );
    }

    /**
     * @throws SSHError
     */
    public function delete(string $name): void
    {
        $this->service->server->ssh()->exec(
            view($this->getScriptView('delete'), [
                'name' => $name,
            ]),
            'delete-database'
        );
    }

    /**
     * @throws SSHError
     */
    public function createUser(string $username, string $password, string $host): void
    {
        $this->service->server->ssh()->exec(
            view($this->getScriptView('create-user'), [
                'username' => $username,
                'password' => $password,
                'host' => $host,
            ]),
            'create-user'
        );
    }

    /**
     * @throws SSHError
     */
    public function deleteUser(string $username, string $host): void
    {
        $this->service->server->ssh()->exec(
            view($this->getScriptView('delete-user'), [
                'username' => $username,
                'host' => $host,
            ]),
            'delete-user'
        );
    }

    /**
     * @throws SSHError
     */
    public function link(string $username, string $host, array $databases): void
    {
        $ssh = $this->service->server->ssh();
        $version = $this->service->version;

        foreach ($databases as $database) {
            $ssh->exec(
                view($this->getScriptView('link'), [
                    'username' => $username,
                    'host' => $host,
                    'database' => $database,
                    'version' => $version,
                ]),
                'link-user-to-database'
            );
        }
    }

    /**
     * @throws SSHError
     */
    public function unlink(string $username, string $host): void
    {
        $version = $this->service->version;

        $this->service->server->ssh()->exec(
            view($this->getScriptView('unlink'), [
                'username' => $username,
                'host' => $host,
                'version' => $version,
            ]),
            'unlink-user-from-databases'
        );
    }

    /**
     * @throws SSHError
     */
    public function runBackup(BackupFile $backupFile): void
    {
        // backup
        $this->service->server->ssh()->exec(
            view($this->getScriptView('backup'), [
                'file' => $backupFile->name,
                'database' => $backupFile->backup->database->name,
            ]),
            'backup-database'
        );

        // upload to storage
        $upload = $backupFile->backup->storage->provider()->ssh($this->service->server)->upload(
            $backupFile->tempPath(),
            $backupFile->path(),
        );

        // cleanup
        $this->service->server->ssh()->exec('rm '.$backupFile->tempPath());

        $backupFile->size = $upload['size'];
        $backupFile->save();
    }

    /**
     * @throws SSHError
     */
    public function restoreBackup(BackupFile $backupFile, string $database): void
    {
        // download
        $backupFile->backup->storage->provider()->ssh($this->service->server)->download(
            $backupFile->path(),
            $backupFile->tempPath(),
        );

        $this->service->server->ssh()->exec(
            view($this->getScriptView('restore'), [
                'database' => $database,
                'file' => rtrim($backupFile->tempPath(), '.zip'),
            ]),
            'restore-database'
        );
    }

    /**
     * @throws SSHError
     */
    public function getCharsets(): array
    {
        $data = $this->service->server->ssh()->exec(
            view($this->getScriptView('get-charsets')),
            'get-database-charsets'
        );

        $charsets = $this->tableToArray($data);

        $results = [];
        $charsetCollations = [];

        foreach ($charsets as $key => $charset) {
            if (empty($charsetCollations[$charset[1]])) {
                $charsetCollations[$charset[1]] = [];
            }

            $charsetCollations[$charset[1]][] = $charset[0];

            if ($charset[3] === 'Yes') {
                $results[$charset[1]] = [
                    'default' => $charset[0],
                    'list' => [],
                ];

                continue;
            }

            if ($key == count($charsets) - 1) {
                $results[$charset[1]] = [
                    'default' => null,
                    'list' => [],
                ];
            }
        }

        foreach (array_keys($results) as $charset) {
            $results[$charset]['list'] = $charsetCollations[$charset];
        }

        ksort($results);

        return [
            'charsets' => $results,
            'defaultCharset' => $this->defaultCharset,
        ];
    }

    /**
     * @throws SSHError
     */
    public function getDatabases(): array
    {
        $data = $this->service->server->ssh()->exec(
            view($this->getScriptView('get-db-list')),
            'get-db-list'
        );

        $databases = $this->tableToArray($data);

        return array_values(array_filter($databases, fn ($database): bool => ! in_array($database[0], $this->systemDbs)));
    }

    /**
     * @throws SSHError
     */
    public function getUsers(): array
    {
        $data = $this->service->server->ssh()->exec(
            view($this->getScriptView('get-users-list')),
            'get-users-list'
        );

        $users = $this->tableToArray($data);

        $users = array_values(array_filter($users, fn ($users): bool => ! in_array($users[0], $this->systemUsers)));

        foreach ($users as $key => $user) {
            $databases = explode(',', $user[2]);
            $databases = array_values(array_filter($databases, fn ($database): bool => ! in_array($database, $this->systemDbs)));
            $users[$key][2] = implode(',', $databases);
        }

        return $users;
    }

    /**
     * @return array<array<string>>
     */
    protected function tableToArray(string $data, bool $keepHeader = false): array
    {
        $lines = explode("\n", trim($data));

        if (! $keepHeader) {
            for ($i = 0; $i < $this->headerLines; $i++) {
                array_shift($lines);
            }
        }

        if ($this->removeLastRow) {
            array_pop($lines);
        }

        $rows = [];
        foreach ($lines as $line) {
            $separator = $this->separator === '' || $this->separator === '0' ? "\t" : $this->separator;
            $row = explode($separator, $line);
            $row = array_map('trim', $row);
            $rows[] = $row;
        }

        return $rows;
    }
}
