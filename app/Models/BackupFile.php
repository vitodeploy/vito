<?php

namespace App\Models;

use App\Actions\Backup\ManageBackupFile;
use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteBackupFileFromProvider;
use App\StorageProviders\Dropbox;
use App\StorageProviders\FTP;
use App\StorageProviders\Local;
use App\StorageProviders\S3;
use Carbon\Carbon;
use Database\Factories\BackupFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 * @property int $backup_id
 * @property string $name
 * @property int $size
 * @property BackupFileStatus $status
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
        'status' => BackupFileStatus::class,
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
        $extension = $this->getBackupExtension();

        return '/home/'.$this->backup->server->getSshUser().'/'.$this->name.$extension;
    }

    public function path(): string
    {
        $storage = $this->backup->storage;

        // For file backups, use the path field; for database backups, use database name
        $backupName = $this->backup->type === BackupType::FILE
            ? basename($this->backup->path)
            : $this->backup->database->name;

        $extension = $this->getBackupExtension();

        return match ($storage->provider) {
            Dropbox::id() => '/'.$backupName.'/'.$this->name.$extension,
            S3::id(), FTP::id(), Local::id() => implode('/', [
                rtrim((string) $storage->credentials['path'], '/'),
                $backupName,
                $this->name.$extension,
            ]),
            default => '',
        };
    }

    public function deleteFile(): void
    {
        try {
            $storage = $this->backup->storage->provider()->ssh($this->backup->server);
            $storage->delete($this->path());
        } catch (Throwable) {
            Notifier::send($this->backup->server, new FailedToDeleteBackupFileFromProvider($this));
        }

        $this->delete();
    }

    private function getBackupExtension(): string
    {
        if ($this->backup->type === BackupType::DATABASE) {
            return '.zip';
        }

        return '.tar.gz';
    }
}
