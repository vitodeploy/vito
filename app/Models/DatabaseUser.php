<?php

namespace App\Models;

use App\Enums\DatabaseUserStatus;
use Database\Factories\DatabaseUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $username
 * @property string $password
 * @property array<string> $databases
 * @property string $host
 * @property string $status
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

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        DatabaseUserStatus::READY => 'success',
        DatabaseUserStatus::CREATING => 'warning',
        DatabaseUserStatus::DELETING => 'warning',
        DatabaseUserStatus::FAILED => 'danger',
    ];
}
