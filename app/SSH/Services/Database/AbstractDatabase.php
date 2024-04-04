<?php

namespace App\SSH\Services\Database;

use App\Models\BackupFile;
use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;

abstract class AbstractDatabase extends AbstractService implements Database
{
    use HasScripts;

    abstract protected function getScriptsDir(): string;

    public function install(): void
    {
        $version = $this->service->version;
        $command = $this->getScript($this->service->name.'/install-'.$version.'.sh');
        $this->service->server->ssh()->exec($command, 'install-'.$this->service->name.'-'.$version);
        $status = $this->service->server->systemd()->status($this->service->unit);
        $this->service->validateInstall($status);
    }

    public function uninstall(): void
    {
        //
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
        $this->service->server->ssh()->exec('rm '.$backupFile->name.'.zip');

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
