<?php

namespace App\Models;

use App\Enums\VitoBackupFileStatus;
use Database\Factories\VitoBackupFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $vito_backup_id
 * @property string $name
 * @property int $size
 * @property VitoBackupFileStatus $status
 * @property string|null $path
 * @property VitoBackup|null $vitoBackup
 */
class VitoBackupFile extends AbstractModel
{
    /** @use HasFactory<VitoBackupFileFactory> */
    use HasFactory;

    protected $fillable = [
        'vito_backup_id',
        'name',
        'size',
        'status',
        'path',
    ];

    protected $casts = [
        'vito_backup_id' => 'integer',
        'size' => 'integer',
        'status' => VitoBackupFileStatus::class,
    ];

    /**
     * @return BelongsTo<VitoBackup, covariant $this>
     */
    public function vitoBackup(): BelongsTo
    {
        return $this->belongsTo(VitoBackup::class, 'vito_backup_id');
    }
}
