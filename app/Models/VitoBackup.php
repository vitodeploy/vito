<?php

namespace App\Models;

use App\Enums\VitoBackupStatus;
use Database\Factories\VitoBackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $frequency
 * @property int $keep_backups
 * @property int $storage_id
 * @property VitoBackupStatus $status
 * @property StorageProvider|null $storage
 * @property VitoBackupFile[] $files
 */
class VitoBackup extends AbstractModel
{
    /** @use HasFactory<VitoBackupFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'frequency',
        'keep_backups',
        'storage_id',
        'status',
    ];

    protected $casts = [
        'keep_backups' => 'integer',
        'storage_id' => 'integer',
        'status' => VitoBackupStatus::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function ($backup): void {
            /** @var VitoBackup $backup */
            $backup->files()->each(function ($file): void {
                /** @var VitoBackupFile $file */
                $file->delete();
            });
        });
    }

    /**
     * @return BelongsTo<StorageProvider, covariant $this>
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(StorageProvider::class, 'storage_id');
    }

    /**
     * @return HasMany<VitoBackupFile, covariant $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(VitoBackupFile::class, 'vito_backup_id');
    }
}
