<?php

namespace App\Models;

use App\Enums\DatabaseUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static array $statusColors = [
        DatabaseUserStatus::READY => 'success',
        DatabaseUserStatus::CREATING => 'warning',
        DatabaseUserStatus::DELETING => 'warning',
        DatabaseUserStatus::FAILED => 'danger',
    ];
}
