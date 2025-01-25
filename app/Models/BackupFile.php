<?php

namespace App\Models;

use App\Actions\Database\ManageBackupFile;
use App\Enums\BackupFileStatus;
use App\Enums\StorageProvider as StorageProviderAlias;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $backup_id
 * @property string $name
 * @property int $size
 * @property string $status
 * @property string $restored_to
 * @property Carbon $restored_at
 * @property Backup $backup
 */
class BackupFile extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'name',
        'size',
        'status',
        'restored_to',
        'restored_at',
    ];

    protected $casts = [
        'backup_id' => 'integer',
        'restored_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (BackupFile $backupFile) {
            $keep = $backupFile->backup->keep_backups;
            if ($backupFile->backup->files()->count() > $keep) {
                /* @var BackupFile $lastFileToKeep */
                $lastFileToKeep = $backupFile->backup->files()->orderByDesc('id')->skip($keep)->first();
                if ($lastFileToKeep) {
                    $files = $backupFile->backup->files()
                        ->where('id', '<=', $lastFileToKeep->id)
                        ->get();
                    foreach ($files as $file) {
                        app(ManageBackupFile::class)->delete($file);
                    }
                }
            }
        });
    }

    public static array $statusColors = [
        BackupFileStatus::CREATED => 'success',
        BackupFileStatus::CREATING => 'warning',
        BackupFileStatus::FAILED => 'danger',
        BackupFileStatus::DELETING => 'warning',
        BackupFileStatus::RESTORING => 'warning',
        BackupFileStatus::RESTORED => 'primary',
        BackupFileStatus::RESTORE_FAILED => 'danger',
    ];

    public function isAvailable(): bool
    {
        return ! in_array(
            $this->status,
            [BackupFileStatus::CREATING, BackupFileStatus::FAILED, BackupFileStatus::DELETING]
        );
    }

    public function isLocal(): bool
    {
        return $this->backup->storage->provider === StorageProviderAlias::LOCAL;
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function tempPath(): string
    {
        return '/home/'.$this->backup->server->getSshUser().'/'.$this->name.'.zip';
    }

    public function path(): string
    {
        $storage = $this->backup->storage;
        $databaseName = $this->backup->database->name;

        return match ($storage->provider) {
            StorageProviderAlias::DROPBOX => '/'.$databaseName.'/'.$this->name.'.zip',
            StorageProviderAlias::S3, StorageProviderAlias::FTP, StorageProviderAlias::LOCAL => implode('/', [
                rtrim($storage->credentials['path'], '/'),
                $databaseName,
                $this->name.'.zip',
            ]),
            default => '',
        };
    }

    public function deleteFile(): void
    {
        try {
            $storage = $this->backup->storage->provider()->ssh($this->backup->server);
            $storage->delete($this->path());
        } finally {
            $this->delete();
        }
    }
}
