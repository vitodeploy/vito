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
use App\StorageProviders\SFTP;
use Carbon\Carbon;
use Database\Factories\BackupFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property int $backup_id
 * @property string $name
 * @property ?int $size
 * @property ?string $database_engine
 * @property ?string $database_version
 * @property BackupFileStatus $status
 * @property ?string $message
 * @property ?string $restored_to
 * @property ?Carbon $restored_at
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
            [
                BackupFileStatus::CREATING,
                BackupFileStatus::FAILED,
                BackupFileStatus::DELETING,
                BackupFileStatus::DELETE_FAILED,
            ]
        );
    }

    public function isLocal(): bool
    {
        return $this->backup->storage->provider === Local::id();
    }

    public static function normalizeVersion(?string $raw): ?string
    {
        if ($raw === null || ! preg_match('/\d+(\.\d+){0,2}/', trim($raw), $matches)) {
            return null;
        }

        return $matches[0];
    }

    public function restoreCompatibilityError(Database $target): ?string
    {
        $targetServer = $target->server;

        if ($targetServer->id === $this->backup->server_id) {
            return null;
        }

        if ($this->isLocal()) {
            return 'Backups on local storage can only be restored to their original server.';
        }

        if (! $this->database_engine || ! $this->database_version) {
            return 'This backup file has no source database metadata and can only be restored to its original server.';
        }

        $service = $targetServer->database();
        if (! $service) {
            return 'The selected server has no database service.';
        }

        if ($service->name !== $this->database_engine) {
            return "The backup was taken from {$this->database_engine} and cannot be restored to a server running {$service->name}.";
        }

        $targetVersion = static::normalizeVersion($service->installed_version ?: $service->version);
        if ($targetVersion === null) {
            return "Cannot determine the target server's database version.";
        }

        if (! static::versionGte($targetVersion, $this->database_version)) {
            return "The target database version ({$targetVersion}) is lower than the backup source version ({$this->database_version}).";
        }

        return null;
    }

    public static function versionGte(string $target, string $source): bool
    {
        $targetParts = explode('.', $target);
        $sourceParts = explode('.', $source);
        $length = min(count($targetParts), count($sourceParts));

        return version_compare(
            implode('.', array_slice($targetParts, 0, $length)),
            implode('.', array_slice($sourceParts, 0, $length)),
            '>='
        );
    }

    /**
     * @return BelongsTo<Backup, covariant $this>
     */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function tempPath(?Server $server = null): string
    {
        $extension = $this->getBackupExtension();

        return '/home/'.($server ?? $this->backup->server)->getSshUser().'/'.$this->name.$extension;
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
            S3::id(), FTP::id(), SFTP::id(), Local::id() => implode('/', [
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
        } catch (Throwable $e) {
            $this->status = BackupFileStatus::DELETE_FAILED;
            $this->message = Str::limit($e->getMessage(), 1000);
            $this->save();
            Notifier::send($this->backup->server, new FailedToDeleteBackupFileFromProvider($this));

            return;
        }

        $this->delete();
    }

    private function getBackupExtension(): string
    {
        if ($this->backup->type === BackupType::DATABASE) {
            return '.sql.gz';
        }

        return '.tar.gz';
    }
}
