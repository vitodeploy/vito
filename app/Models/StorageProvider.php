<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property string $provider
 * @property string $label
 * @property string $token
 * @property string $refresh_token
 * @property bool $connected
 * @property Carbon $token_expires_at
 * @property User $user
 */
class StorageProvider extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'label',
        'token',
        'refresh_token',
        'connected',
        'token_expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'connected' => 'boolean',
        'token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): \App\Contracts\StorageProvider
    {
        $providerClass = config('core.storage_providers_class')[$this->provider];

        return new $providerClass($this);
    }
}
