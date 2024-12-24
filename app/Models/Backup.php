<?php

namespace App\Models;

use App\Enums\BackupStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Backup extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'type',
        'server_id',
        'storage_id',
        'database_id',
        'interval',
        'keep_backups',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'storage_id' => 'integer',
        'database_id' => 'integer',
        'keep_backups' => 'integer',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Backup $backup) {
            $backup->files()->each(function (BackupFile $file) {
                $file->delete();
            });
        });
    }

    public static array $statusColors = [
        BackupStatus::RUNNING => 'success',
        BackupStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<StorageProvider, $this>
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(StorageProvider::class, 'storage_id');
    }

    /**
     * @return BelongsTo<Database, $this>
     */
    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class)->withTrashed();
    }

    /**
     * @return HasMany<BackupFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(BackupFile::class, 'backup_id');
    }

    /**
     * @return HasOne<BackupFile, $this>
     */
    public function lastFile(): HasOne
    {
        return $this->hasOne(BackupFile::class, 'backup_id')->latest();
    }
}
