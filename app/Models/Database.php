<?php

namespace App\Models;

use App\Enums\DatabaseStatus;
use Carbon\Carbon;
use Database\Factories\DatabaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $server_id
 * @property string $name
 * @property string $collation
 * @property string $charset
 * @property string $status
 * @property Server $server
 * @property Backup[] $backups
 * @property ?Carbon $deleted_at
 */
class Database extends AbstractModel
{
    /** @use HasFactory<DatabaseFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'server_id',
        'name',
        'collation',
        'charset',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Database $database): void {
            $database->server->databaseUsers()->each(function ($user) use ($database): void {
                /** @var DatabaseUser $user */
                $databases = $user->databases;
                if ($databases && in_array($database->name, $databases)) {
                    unset($databases[array_search($database->name, $databases)]);
                    $user->databases = $databases;
                    $user->save();
                }
            });
        });
    }

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        DatabaseStatus::READY => 'success',
        DatabaseStatus::CREATING => 'warning',
        DatabaseStatus::DELETING => 'warning',
        DatabaseStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<Backup, covariant $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class)->where('type', 'database');
    }
}
