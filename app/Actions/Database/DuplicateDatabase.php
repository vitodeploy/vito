<?php

namespace App\Actions\Database;

use App\Enums\BackupStatus;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\StorageProvider;
use Illuminate\Validation\Rule;

class DuplicateDatabase
{
    public function __construct(
        private readonly RunBackup $runBackup,
        private readonly RestoreBackup $restoreBackup,
        private readonly ManageBackup $manageBackup,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function duplicate(Database $sourceDatabase, array $input): Database
    {
        // Create new database with same settings
        $newDatabase = app(CreateDatabase::class)->create($sourceDatabase->server, [
            'name' => $input['name'],
            'charset' => $sourceDatabase->charset,
            'collation' => $sourceDatabase->collation,
        ]);

        // Link database users
        $databaseUsers = DatabaseUser::query()
            ->where('server_id', $sourceDatabase->server_id)
            ->whereJsonContains('databases', $sourceDatabase->name)
            ->get();

        foreach ($databaseUsers as $databaseUser) {
            $databases = $databaseUser->databases;
            $databases[] = $newDatabase->name;

            app(LinkUser::class)->link($databaseUser, [
                'databases' => $databases,
            ]);
        }

        // Create backup of source database
        $storageProvider = StorageProvider::getByProjectId($sourceDatabase->server->project_id)
            ->where('provider', 'local')
            ->first();

        if (! $storageProvider) {
            throw new \RuntimeException('No local storage provider found for this project. To use this feature, add at least one local storage provider.');
        }

        $backup = $sourceDatabase->backups()->create([
            'type' => 'database',
            'server_id' => $sourceDatabase->server_id,
            'database_id' => $sourceDatabase->id,
            'storage_id' => $storageProvider->id,
            'interval' => 'once',
            'keep_backups' => 1,
            'status' => BackupStatus::RUNNING,
        ]);

        // Run backup and restore to new database
        $backupFile = $this->runBackup->run($backup);
        $this->restoreBackup->restore($backupFile, [
            'database' => $newDatabase->id,
        ]);

        // Clean up the temporary backup
        $this->manageBackup->delete($backup);

        return $newDatabase;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(Database $sourceDatabase): array
    {
        return [
            'name' => [
                'required',
                'alpha_dash',
                Rule::unique('databases', 'name')->where('server_id', $sourceDatabase->server_id),
            ],
        ];
    }
}
