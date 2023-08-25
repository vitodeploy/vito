<?php

namespace App\ServiceHandlers\Database;

use App\Models\BackupFile;
use App\SSHCommands\Database\BackupDatabaseCommand;
use App\SSHCommands\Database\CreateCommand;
use App\SSHCommands\Database\CreateUserCommand;
use App\SSHCommands\Database\DeleteCommand;
use App\SSHCommands\Database\DeleteUserCommand;
use App\SSHCommands\Database\LinkCommand;
use App\SSHCommands\Database\RestoreDatabaseCommand;
use App\SSHCommands\Database\UnlinkCommand;
use Throwable;

class Mysql extends AbstractDatabase
{
    /**
     * @throws Throwable
     */
    public function create(string $name): void
    {
        $this->service->server->ssh()->exec(
            new CreateCommand('mysql', $name),
            'create-database'
        );
    }

    /**
     * @throws Throwable
     */
    public function delete(string $name): void
    {
        $this->service->server->ssh()->exec(
            new DeleteCommand('mysql', $name),
            'delete-database'
        );
    }

    /**
     * @throws Throwable
     */
    public function createUser(string $username, string $password, string $host): void
    {
        $this->service->server->ssh()->exec(
            new CreateUserCommand('mysql', $username, $password, $host),
            'create-user'
        );
    }

    /**
     * @throws Throwable
     */
    public function deleteUser(string $username, string $host): void
    {
        $this->service->server->ssh()->exec(
            new DeleteUserCommand('mysql', $username, $host),
            'delete-user'
        );
    }

    /**
     * @throws Throwable
     */
    public function link(string $username, string $host, array $databases): void
    {
        $ssh = $this->service->server->ssh();
        foreach ($databases as $database) {
            $ssh->exec(
                new LinkCommand('mysql', $username, $host, $database),
                'link-user-to-databases'
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function unlink(string $username, string $host): void
    {
        $this->service->server->ssh()->exec(
            new UnlinkCommand('mysql', $username, $host),
            'unlink-user-from-database'
        );
    }

    /**
     * @throws Throwable
     */
    public function runBackup(BackupFile $backupFile): void
    {
        // backup
        $this->service->server->ssh()->exec(
            new BackupDatabaseCommand(
                'mysql',
                $backupFile->backup->database->name,
                $backupFile->name
            ),
            'backup-database'
        );

        // upload to storage
        $upload = $backupFile->backup->storage->provider()->upload(
            $backupFile->backup->server,
            $backupFile->path,
            $backupFile->storage_path,
        );

        // cleanup
        $this->service->server->ssh()->exec('rm '.$backupFile->name.'.zip');

        $backupFile->size = $upload['size'];
        $backupFile->save();
    }

    /**
     * @throws Throwable
     */
    public function restoreBackup(BackupFile $backupFile, string $database): void
    {
        // download
        $backupFile->backup->storage->provider()->download(
            $backupFile->backup->server,
            $backupFile->storage_path,
            $backupFile->name.'.zip',
        );

        // restore
        $this->service->server->ssh()->exec(
            new RestoreDatabaseCommand(
                'mysql',
                $database,
                $backupFile->name
            ),
            'restore-database'
        );
    }
}
