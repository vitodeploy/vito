<?php

namespace App\Actions\Backup;

use App\DTOs\SocketEventDTO;
use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Enums\DatabaseStatus;
use App\Events\SocketEvent;
use App\Http\Resources\BackupFileResource;
use App\Jobs\Backup\RestoreDatabaseJob;
use App\Jobs\Backup\RestoreFileJob;
use App\Models\BackupFile;
use App\Models\Database;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RestoreBackup
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function restore(BackupFile $backupFile, array $input): void
    {
        $this->validate($backupFile, $input, $backupFile->backup->type);

        $backup = $backupFile->backup;
        $backupFile->status = BackupFileStatus::RESTORING;
        $backupFile->message = null;

        if ($backup->type === BackupType::DATABASE) {
            $this->restoreDatabase($backupFile, $input);
        }

        if ($backup->type === BackupType::FILE) {
            $this->restoreFile($backupFile, $input);
        }

        app(BroadcastBackupUpdate::class)->broadcast($backup);
    }

    private function restoreDatabase(BackupFile $backupFile, array $input): void
    {
        /** @var Database $database */
        $database = Database::query()->with('server')->findOrFail($input['database']);
        $backupFile->restored_to = $database->server_id === $backupFile->backup->server_id
            ? $database->name
            : "{$database->name} ({$database->server->name})";
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

    private function validate(BackupFile $backupFile, array $input, BackupType $backupType): void
    {
        $rules = [];

        if ($backupType === BackupType::DATABASE) {
            $rules['database'] = [
                'required',
                Rule::exists('databases', 'id')
                    ->whereNull('deleted_at')
                    ->whereIn('server_id', $backupFile->backup->server->project->servers()->pluck('id')->all()),
                function (string $attribute, mixed $value, Closure $fail) use ($backupFile): void {
                    /** @var ?Database $database */
                    $database = Database::query()->with('server')->find($value);
                    if (! $database) {
                        return;
                    }

                    if ($database->server->project_id !== $backupFile->backup->server->project_id) {
                        $fail('The selected database does not belong to this project.');

                        return;
                    }

                    if (! $database->server->isReady()) {
                        $fail('The selected server is not ready.');

                        return;
                    }

                    if ($database->status !== DatabaseStatus::READY) {
                        $fail('The selected database is not ready.');

                        return;
                    }

                    $error = $backupFile->restoreCompatibilityError($database);
                    if ($error !== null) {
                        $fail($error);
                    }
                },
            ];
        } else {
            $rules['path'] = [
                'required',
                'string',
                'regex:/^\/[^\r\n]*$/',
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
