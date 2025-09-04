<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plugin extends Model
{
    protected $fillable = [
        'name',
        'version',
        'description',
        'repo',
        'namespace',
        'is_enabled',
        'is_installed',
        'updates_available',
        'config',
        'priority',
        'last_error_at',
        'error_count',
        'folder',
        'username',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_installed' => 'boolean',
        'config' => 'array',
        'requirements' => 'array',
        'last_error_at' => 'datetime',
        'error_count' => 'integer',
        'priority' => 'integer',
        'updates_available' => 'boolean',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(PluginError::class);
    }
}
