<?php

namespace App\Models;

use App\Enums\EnvironmentAttachmentStatus;
use Database\Factories\EnvironmentAttachmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $environment_id
 * @property int $server_id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property ?string $alias
 * @property array{username?: string, password: string} $credentials
 * @property array<int, array{server_id: int, host: string, target_host: string, network_id: ?int, database_user_id: ?int, firewall_rule_id: ?int, ready: bool}> $access
 * @property ?int $firewall_rule_id
 * @property EnvironmentAttachmentStatus $status
 * @property ?string $last_error
 * @property Environment $environment
 * @property Server $server
 * @property Database|Service|null $attachable
 */
class EnvironmentAttachment extends AbstractModel
{
    /** @use HasFactory<EnvironmentAttachmentFactory> */
    use HasFactory;

    protected $casts = [
        'environment_id' => 'integer',
        'server_id' => 'integer',
        'attachable_id' => 'integer',
        'credentials' => 'encrypted:json',
        'access' => 'array',
        'firewall_rule_id' => 'integer',
        'status' => EnvironmentAttachmentStatus::class,
    ];

    protected $hidden = ['credentials', 'access'];

    /** @return BelongsTo<Environment, covariant $this> */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /** @return BelongsTo<Server, covariant $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /** @return MorphTo<Model, covariant $this> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<DatabaseUser, covariant $this> */
    public function databaseUsers(): HasMany
    {
        return $this->hasMany(DatabaseUser::class);
    }

    /** @return HasMany<FirewallRule, covariant $this> */
    public function firewallRules(): HasMany
    {
        return $this->hasMany(FirewallRule::class);
    }

    public function service(): ?Service
    {
        return $this->attachable instanceof Database ? $this->server->database() : $this->attachable;
    }

    public function kind(): string
    {
        return $this->attachable_type === (new Database)->getMorphClass() ? 'database' : 'redis';
    }

    public function isDeleting(): bool
    {
        return in_array($this->status, [EnvironmentAttachmentStatus::DELETING, EnvironmentAttachmentStatus::DELETION_FAILED], true);
    }

    /** @return Builder<self> */
    public static function forService(Service $service): Builder
    {
        return self::query()->where('server_id', $service->server_id)
            ->where('attachable_type', $service->type === 'database' ? (new Database)->getMorphClass() : $service->getMorphClass())
            ->when($service->type !== 'database', fn (Builder $query) => $query->where('attachable_id', $service->id));
    }
}
