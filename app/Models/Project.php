<?php

namespace App\Models;

use App\Traits\HasTimezoneTimestamps;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User $user
 * @property Collection<int, Server> $servers
 * @property Collection<int, User> $users
 * @property Collection<int, NotificationChannel> $notificationChannels
 * @property Collection<int, SourceControl> $sourceControls
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    use HasTimezoneTimestamps;

    protected $fillable = [
        'user_id',
        'name',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Project $project): void {
            $project->servers()->each(function ($server): void {
                /** @var Server $server */
                $server->delete();
            });
        });
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Server, covariant $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * @return HasMany<NotificationChannel, covariant $this>
     */
    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    /**
     * @return BelongsToMany<User, covariant $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_project')->withTimestamps();
    }

    /**
     * @return HasMany<SourceControl, covariant $this>
     */
    public function sourceControls(): HasMany
    {
        return $this->hasMany(SourceControl::class);
    }

    /**
     * @return HasMany<Tag, covariant $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }
}
