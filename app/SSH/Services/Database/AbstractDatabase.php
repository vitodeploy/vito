<?php

namespace App\SSH\Services\Database;

use App\Enums\BackupStatus;
use App\Exceptions\ServiceInstallationFailed;
use App\Exceptions\SSHError;
use App\Models\BackupFile;
use App\SSH\Services\AbstractService;
use Closure;

abstract class AbstractDatabase extends AbstractService implements Database
{
    protected function getScriptView(string $script): string
    {
        return 'ssh.services.database.'.$this->service->name.'.'.$script;
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
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
        $status = $this->service->server->systemd()->status($this->service->unit);
        $this->service->validateInstall($status);
        $this->service->server->os()->cleanup();
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail) {
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
    public function create(string $name): void
    {
        $this->service->server->ssh()->exec(
            view($this->getScriptView('create'), [
                'name' => $name,
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
}
