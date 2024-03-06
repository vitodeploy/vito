<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $username
 * @property string $password
 * @property array $databases
 * @property string $host
 * @property string $status
 * @property Server $server
 * @property string $full_user
 */
class DatabaseUser extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'username',
        'password',
        'databases',
        'host',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'password' => 'encrypted',
        'databases' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeHasDatabase(Builder $query, string $databaseName): Builder
    {
        return $query->where('databases', 'like', "%\"$databaseName\"%");
    }

    public function getFullUserAttribute(): string
    {
        return $this->username.'@'.$this->host;
    }
}
