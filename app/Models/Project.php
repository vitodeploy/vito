<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\HasTimezoneTimestamps;
use Carbon\Carbon;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User $user
 * @property Collection<int, Server> $servers
 * @property Collection<int, Site> $sites
 * @property Collection<int, UserProject> $users
 * @property Collection<int, NotificationChannel> $notificationChannels
 * @property Collection<int, SourceControl> $sourceControls
 * @property Collection<int, User> $registeredUsers
 * @property Collection<int, Workflow> $workflows
 * @property Collection<int, Domain> $domains
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasTimezoneTimestamps;

    protected $fillable = [
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
     * @return HasMany<Server, covariant $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * @return HasManyThrough<Site, Server, covariant $this>
     */
    public function sites(): HasManyThrough
    {
        return $this->hasManyThrough(Site::class, Server::class);
    }

    /**
     * @return HasMany<NotificationChannel, covariant $this>
     */
    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserProject::class, 'project_id');
    }

    /**
     * @return HasMany<SourceControl, covariant $this>
     */
    public function sourceControls(): HasMany
    {
        return $this->hasMany(SourceControl::class);
    }

    public function registeredUsers(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, UserProject::class, 'project_id', 'id', 'id', 'user_id');
    }

    public function hasRoles(User $user, array $roles): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->whereIn('role', $roles)
            ->exists();
    }

    public function role(User $user): UserRole
    {
        /** @var UserProject $user */
        $user = $this->users()->where('user_id', $user->id)->firstOrFail();

        return $user->role;
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}
