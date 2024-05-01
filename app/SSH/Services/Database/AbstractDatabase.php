<?php

namespace App\SSH\Services\Database;

use App\Enums\BackupStatus;
use App\Models\BackupFile;
use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;

abstract class AbstractDatabase extends AbstractService implements Database
{
    use HasScripts;

    abstract protected function getScriptsDir(): string;

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

    public function install(): void
    {
        $version = $this->service->version;
        $command = $this->getScript($this->service->name.'/install-'.$version.'.sh');
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

    public function uninstall(): void
    {
        $version = $this->service->version;
        $command = $this->getScript($this->service->name.'/uninstall.sh');
        $this->service->server->ssh()->exec($command, 'uninstall-'.$this->service->name.'-'.$version);
        $this->service->server->os()->cleanup();
    }

    public function create(string $name): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/create.sh', [
                'name' => $name,
            ]),
            'create-database'
        );
    }

    public function delete(string $name): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/delete.sh', [
                'name' => $name,
            ]),
            'delete-database'
        );
    }

    public function createUser(string $username, string $password, string $host): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/create-user.sh', [
                'username' => $username,
                'password' => $password,
                'host' => $host,
            ]),
            'create-user'
        );
    }

    public function deleteUser(string $username, string $host): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/delete-user.sh', [
                'username' => $username,
                'host' => $host,
            ]),
            'delete-user'
        );
    }

    public function link(string $username, string $host, array $databases): void
    {
        $ssh = $this->service->server->ssh();

        foreach ($databases as $database) {
            $ssh->exec(
                $this->getScript($this->getScriptsDir().'/link.sh', [
                    'username' => $username,
                    'host' => $host,
                    'database' => $database,
                ]),
                'link-user-to-database'
            );
        }
    }

    public function unlink(string $username, string $host): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/unlink.sh', [
                'username' => $username,
                'host' => $host,
            ]),
            'unlink-user-from-databases'
        );
    }

    public function runBackup(BackupFile $backupFile): void
    {
        // backup
        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/backup.sh', [
                'file' => $backupFile->name,
                'database' => $backupFile->backup->database->name,
            ]),
            'backup-database'
        );

        // upload to storage
        $upload = $backupFile->backup->storage->provider()->ssh($this->service->server)->upload(
            $backupFile->path(),
            $backupFile->storagePath(),
        );

        // cleanup
        $this->service->server->ssh()->exec('rm '.$backupFile->path());

        $backupFile->size = $upload['size'];
        $backupFile->save();
    }

    public function restoreBackup(BackupFile $backupFile, string $database): void
    {
        // download
        $backupFile->backup->storage->provider()->ssh($this->service->server)->download(
            $backupFile->storagePath(),
            $backupFile->name.'.zip',
        );

        $this->service->server->ssh()->exec(
            $this->getScript($this->getScriptsDir().'/restore.sh', [
                'database' => $database,
                'file' => $backupFile->name,
            ]),
            'restore-database'
        );
    }
}
