<?php

namespace App\Models;

use App\Enums\DatabaseStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Database extends AbstractModel
{
    use HasFactory;
    use SoftDeletes;

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
        });
    }

    public static array $statusColors = [
        DatabaseStatus::READY => 'success',
        DatabaseStatus::CREATING => 'warning',
        DatabaseStatus::DELETING => 'warning',
        DatabaseStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<Backup, $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class)->where('type', 'database');
    }
}
