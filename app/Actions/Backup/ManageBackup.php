<?php

namespace App\Actions\Backup;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\DatabaseStatus;
use App\Jobs\Backup\DeleteJob;
use App\Models\Backup;
use App\Models\Server;
use App\ValidationRules\CronRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageBackup
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Backup
    {
        $this->validate($server, $input);

        $backupType = BackupType::from($input['type'] ?? BackupType::DATABASE->value);

        $backup = new Backup([
            'type' => $backupType,
            'server_id' => $server->id,
            'database_id' => $backupType === BackupType::DATABASE ? $input['database'] : null,
            'path' => $backupType === BackupType::FILE ? $input['path'] : null,
            'storage_id' => $input['storage'],
            'interval' => $input['interval'] == 'custom' ? $input['custom_interval'] : $input['interval'],
            'keep_backups' => $input['keep'],
        ]);
        $backup->enabled = true;
        $backup->save();

        app(RunBackup::class)->run($backup);

        return $backup;
    }

    public function update(Backup $backup, array $input): void
    {
        $backup->interval = $input['interval'] == 'custom' ? $input['custom_interval'] : $input['interval'];
        $backup->keep_backups = $input['keep'];
        $backup->save();
    }

    public function delete(Backup $backup): void
    {
        $backup->status = BackupStatus::DELETING;
        $backup->save();

        app(BroadcastBackupUpdate::class)->broadcast($backup);

        dispatch(new DeleteJob($backup))->onQueue('ssh');
    }

    public function enable(Backup $backup): void
    {
        if ($backup->status === BackupStatus::DELETING) {
            throw ValidationException::withMessages([
                'backup' => __('This backup is being deleted and cannot be enabled.'),
            ]);
        }

        $backup->enabled = true;
        $backup->save();

        app(BroadcastBackupUpdate::class)->broadcast($backup);
    }

    public function stop(Backup $backup): void
    {
        $backup->enabled = false;
        $backup->save();

        app(BroadcastBackupUpdate::class)->broadcast($backup);
    }

    private function validate(Server $server, array $input): void
    {
        $backupType = BackupType::from($input['type'] ?? BackupType::DATABASE->value);

        $rules = [
            'type' => [
                'required',
                Rule::in([BackupType::DATABASE->value, BackupType::FILE->value]),
            ],
            'storage' => [
                'required',
                Rule::exists('storage_providers', 'id'),
            ],
            'keep' => [
                'required',
                'numeric',
                'min:1',
            ],
            'interval' => [
                'required',
                Rule::in(array_keys(config('core.cronjob_intervals'))),
            ],
        ];

        if ($backupType === BackupType::DATABASE) {
            $rules['database'] = [
                'required',
                Rule::exists('databases', 'id')
                    ->where('server_id', $server->id)
                    ->where('status', DatabaseStatus::READY),
            ];
        } else {
            $rules['path'] = [
                'required',
                'string',
                'min:1',
            ];
        }

        if (isset($input['interval']) && $input['interval'] == 'custom') {
            $rules['custom_interval'] = [
                'required',
                new CronRule,
            ];
        }

        Validator::make($input, $rules)->validate();
    }
}
