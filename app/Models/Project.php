<?php

namespace App\Models;

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
 * @property Collection<Server> $servers
 * @property Collection<NotificationChannel> $notificationChannels
 * @property Collection<SourceControl> $sourceControls
 */
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Project $project) {
            $project->servers()->each(function (Server $server) {
                $server->delete();
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_project')->withTimestamps();
    }

    public function sourceControls(): HasMany
    {
        return $this->hasMany(SourceControl::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }
}
