<?php

namespace App\Services\Database;

use App\Models\BackupFile;
use App\Services\ServiceInterface;

interface Database extends ServiceInterface
{
    public function create(string $name, string $charset, string $collation): void;

    public function delete(string $name): void;

    public function createUser(string $username, string $password, string $host): void;

    public function deleteUser(string $username, string $host): void;

    /**
     * @param  array<string>  $databases
     */
    public function link(string $username, string $host, array $databases): void;

    public function unlink(string $username, string $host): void;

    public function runBackup(BackupFile $backupFile): void;

    public function restoreBackup(BackupFile $backupFile, string $database): void;

    /**
     * @return array<string, mixed>
     */
    public function getCharsets(): array;

    /**
     * @return array<int, array<string>>
     */
    public function getDatabases(): array;

    /**
     * @return array<int, array<string>>
     */
    public function getUsers(): array;
}
