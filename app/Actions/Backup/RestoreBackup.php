<?php

namespace App\Actions\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Jobs\Backup\RestoreDatabaseJob;
use App\Jobs\Backup\RestoreFileJob;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RestoreBackup
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function restore(BackupFile $backupFile, array $input): void
    {
        $this->validate($backupFile->backup->server, $input, $backupFile->backup->type);

        $backup = $backupFile->backup;
        $backupFile->status = BackupFileStatus::RESTORING;
        $backupFile->message = null;

        if ($backup->type === BackupType::DATABASE) {
            $this->restoreDatabase($backupFile, $input);
        }

        if ($backup->type === BackupType::FILE) {
            $this->restoreFile($backupFile, $input);
        }
    }

    private function restoreDatabase(BackupFile $backupFile, array $input): void
    {
        /** @var Database $database */
        $database = Database::query()->findOrFail($input['database']);
        $backupFile->restored_to = $database->name;
        $backupFile->save();
        $this->broadcastFileUpdate($backupFile);

        dispatch(new RestoreDatabaseJob($backupFile, $database))->onQueue('ssh');
    }

    private function restoreFile(BackupFile $backupFile, array $input): void
    {
        $restorePath = $input['path'];
        $owner = $input['owner'] ?? 'vito:vito';
        $permissions = $input['permissions'] ?? '755';

        $backupFile->restored_to = $restorePath;
        $backupFile->save();
        $this->broadcastFileUpdate($backupFile);

        dispatch(new RestoreFileJob($backupFile, $restorePath, $owner, $permissions))->onQueue('ssh');
    }

    private function broadcastFileUpdate(BackupFile $backupFile): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $backupFile->backup->server->project_id,
            type: 'backup-file.updated',
            data: new BackupFileResource($backupFile),
        ));
    }

    private function validate(Server $server, array $input, BackupType $backupType): void
    {
        $rules = [];

        if ($backupType === BackupType::DATABASE) {
            $rules['database'] = [
                'required',
                Rule::exists('databases', 'id')->where('server_id', $server->id),
            ];
        } else {
            $rules['path'] = [
                'required',
                'string',
                'min:1',
            ];
            $rules['owner'] = [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_-]+(:[a-zA-Z0-9_-]+)?$/',
            ];
            $rules['permissions'] = [
                'required',
                'string',
                'regex:/^[0-7]{3,4}$/',
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
