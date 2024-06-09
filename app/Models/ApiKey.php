<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\User;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'api_version',
        'description',
        'secret',
        'full_permissions',
        'permissions',
        'allowed_ips',
        'last_used_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<int, string>
     */
    protected $casts = [
        'full_permissions' => 'boolean',
        'permissions' => 'collection',
        'allowed_ips' => 'collection',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function regenerate()
    {
        $this->update([
            'secret' => Str::random(32),
        ]);

        return $this->secret;
    }

    /**
     * Check if a given permission is allowed for this API key
    */
    public function hasPerm(string $permission): bool
    {
        if($this->full_permissions) {
            return true;
        }

        if(empty($this->permissions)) {
            return false;
        }

        return array_key_exists($permission, $this->permissions->toArray());
    }

    /**
     * Return the human readable last used at date
    */
    public function lastUsedAt()
    {
        return $this->last_used_at ? $this->last_used_at->diffForHumans() : 'Never';
    }

    /**
     * Return the human readable status of the API key
    */
    public function getHumanStatus()
    {
        if($this->expires_at && $this->expires_at->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }
    
}