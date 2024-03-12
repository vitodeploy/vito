<?php

namespace App\Models;

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
 * @property string $path
 * @property string $storage_path
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
                        $file->delete();
                    }
                }
            }
        });

        static::deleting(function (BackupFile $backupFile) {
            $provider = $backupFile->backup->storage->provider();
            $path = $backupFile->storage_path;
            dispatch(function () use ($provider, $path) {
                $provider->delete([$path]);
            });
        });
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function getPathAttribute(): string
    {
        return '/home/'.$this->backup->server->ssh_user.'/'.$this->name.'.zip';
    }

    public function getStoragePathAttribute(): string
    {
        return '/'.$this->name.'.zip';
    }
}
