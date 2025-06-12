<?php

namespace App\Models;

use App\Actions\Database\ManageBackupFile;
use App\Enums\BackupFileStatus;
use App\StorageProviders\Dropbox;
use App\StorageProviders\FTP;
use App\StorageProviders\Local;
use App\StorageProviders\S3;
use Carbon\Carbon;
use Database\Factories\BackupFileFactory;
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
    /** @use HasFactory<BackupFileFactory> */
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
        static::created(function (BackupFile $backupFile): void {
            $keep = $backupFile->backup->keep_backups;
            if ($backupFile->backup->files()->count() > $keep) {
                /** @var ?BackupFile $lastFileToKeep */
                $lastFileToKeep = $backupFile->backup->files()->orderByDesc('id')->skip($keep)->first();
                if ($lastFileToKeep) {
                    $files = $backupFile->backup->files()
                        ->where('id', '<=', $lastFileToKeep->id)
                        ->get();
                    /** @var BackupFile $file */
                    foreach ($files as $file) {
                        app(ManageBackupFile::class)->delete($file);
                    }
                }
            }
        });
    }

    /**
     * @var array<string, string>
     */
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
        return $this->backup->storage->provider === Local::id();
    }

    /**
     * @return BelongsTo<Backup, covariant $this>
     */
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
            Dropbox::id() => '/'.$databaseName.'/'.$this->name.'.zip',
            S3::id(), FTP::id(), Local::id() => implode('/', [
                rtrim((string) $storage->credentials['path'], '/'),
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
