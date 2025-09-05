<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $version
 * @property string|null $description
 * @property string|null $repo
 * @property string $namespace
 * @property bool $is_enabled
 * @property bool $is_installed
 * @property bool $updates_available
 * @property string $folder
 * @property string $username
 * @property Collection<int, PluginError> $errors
 */
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
