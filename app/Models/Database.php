<?php

namespace App\Models;

use App\Enums\DatabaseStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $server_id
 * @property string $name
 * @property string $collation
 * @property string $charset
 * @property DatabaseStatus $status
 * @property Server $server
 * @property Backup[] $backups
 * @property Carbon $deleted_at
 */
class Database extends AbstractModel
{
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
        'status' => DatabaseStatus::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Database $database) {
            $database->server->databaseUsers()->each(function (DatabaseUser $user) use ($database) {
                $databases = $user->databases;
                if ($databases && in_array($database->name, $databases)) {
                    unset($databases[array_search($database->name, $databases)]);
                    $user->databases = $databases;
                    $user->save();
                }
            });
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class)->where('type', 'database');
    }
}
