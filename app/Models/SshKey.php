<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $user_id
 * @property string $name
 * @property string $public_key
 * @property User $user
 * @property Server[] $servers
 */
class SshKey extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'public_key',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'public_key' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class, 'server_ssh_keys')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function existsOnServer(Server $server): bool
    {
        return (bool) $this->servers()->where('id', $server->id)->first();
    }
}
