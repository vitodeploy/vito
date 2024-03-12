<?php

namespace App\SSH\Services\Database;

use App\Models\BackupFile;
use App\SSH\HasScripts;

class Mysql extends AbstractDatabase
{
    use HasScripts;

    public function create(string $name): void
    {
        $this->server->ssh()->exec(
            $this->getScript('mysql/create.sh', [
                'name' => $name,
            ]),
            'create-database'
        );
    }

    public function delete(string $name): void
    {
        $this->server->ssh()->exec(
            $this->getScript('mysql/delete.sh', [
                'name' => $name,
            ]),
            'delete-database'
        );
    }

    public function createUser(string $username, string $password, string $host): void
    {
        $this->server->ssh()->exec(
            $this->getScript('mysql/create-user.sh', [
                'username' => $username,
                'password' => $password,
                'host' => $host,
            ]),
            'create-user'
        );
    }

    public function deleteUser(string $username, string $host): void
    {
        $this->server->ssh()->exec(
            $this->getScript('mysql/delete-user.sh', [
                'username' => $username,
                'host' => $host,
            ]),
            'delete-user'
        );
    }

    public function link(string $username, string $host, array $databases): void
    {
        $ssh = $this->server->ssh();

        foreach ($databases as $database) {
            $ssh->exec(
                $this->getScript('mysql/link.sh', [
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
        $this->server->ssh()->exec(
            $this->getScript('mysql/unlink.sh', [
                'username' => $username,
                'host' => $host,
            ]),
            'unlink-user-from-databases'
        );
    }

    public function runBackup(BackupFile $backupFile): void
    {
        // backup
        $this->server->ssh()->exec(
            $this->getScript('mysql/backup.sh', [
                'file' => $backupFile->name,
                'database' => $backupFile->backup->database->name,
            ]),
            'backup-database'
        );

        // upload to storage
        $upload = $backupFile->backup->storage->provider()->ssh($this->server)->upload(
            $backupFile->path,
            $backupFile->storage_path,
        );

        // cleanup
        $this->server->ssh()->exec('rm '.$backupFile->name.'.zip');

        $backupFile->size = $upload['size'];
        $backupFile->save();
    }

    public function restoreBackup(BackupFile $backupFile, string $database): void
    {
        // download
        $backupFile->backup->storage->provider()->ssh($this->server)->download(
            $backupFile->storage_path,
            $backupFile->name.'.zip',
        );

        $this->server->ssh()->exec(
            $this->getScript('mysql/restore.sh', [
                'database' => $database,
                'file' => $backupFile->name,
            ]),
            'restore-database'
        );
    }
}
