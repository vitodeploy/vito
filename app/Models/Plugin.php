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
        'folder',
        'username',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_installed' => 'boolean',
        'updates_available' => 'boolean',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(PluginError::class);
    }
}
