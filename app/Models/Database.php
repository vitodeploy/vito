<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $server_id
 * @property string $name
 * @property string $status
 * @property Server $server
 * @property Backup[] $backups
 */
class Database extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'name',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
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
            $database->backups()->each(function (Backup $backup) {
                $backup->delete();
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
