<?php

namespace App\Models;

use App\Enums\DatabaseUserPermission;
use App\Enums\DatabaseUserStatus;
use Database\Factories\DatabaseUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $username
 * @property string $password
 * @property array<string> $databases
 * @property DatabaseUserPermission $permission
 * @property string $host
 * @property DatabaseUserStatus $status
 * @property Server $server
 */
class DatabaseUser extends AbstractModel
{
    /** @use HasFactory<DatabaseUserFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'username',
        'password',
        'databases',
        'permission',
        'host',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'password' => 'encrypted',
        'databases' => 'array',
        'permission' => DatabaseUserPermission::class,
        'status' => DatabaseUserStatus::class,
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
