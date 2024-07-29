<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $user_id
 * @property string $profile
 * @property string $provider
 * @property array $credentials
 * @property bool $connected
 * @property User $user
 * @property ?int $project_id
 */
class ServerProvider extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile',
        'provider',
        'credentials',
        'connected',
        'project_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'credentials' => 'encrypted:array',
        'connected' => 'boolean',
        'project_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'provider_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function getByProjectId(int $projectId): Builder
    {
        return self::query()
            ->where('project_id', $projectId)
            ->orWhereNull('project_id');
    }
}
