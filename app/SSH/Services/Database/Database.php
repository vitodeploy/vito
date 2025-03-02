<?php

namespace App\SSH\Services\Database;

use App\Models\BackupFile;

interface Database
{
    public function create(string $name, string $charset, string $collation): void;

    public function delete(string $name): void;

    public function createUser(string $username, string $password, string $host): void;

    public function deleteUser(string $username, string $host): void;

    public function link(string $username, string $host, array $databases): void;

    public function unlink(string $username, string $host): void;

    public function runBackup(BackupFile $backupFile): void;

    public function restoreBackup(BackupFile $backupFile, string $database): void;

    public function getCharsets(): array;

    public function getDatabases(): array;
}
