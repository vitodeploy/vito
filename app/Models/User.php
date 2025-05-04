<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\HasTimezoneTimestamps;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $profile_photo_path
 * @property string $two_factor_recovery_codes
 * @property string $two_factor_secret
 * @property Collection<int, SshKey> $sshKeys
 * @property Collection<int, SourceControl> $sourceControls
 * @property Collection<int, ServerProvider> $serverProviders
 * @property Collection<int, Server> $servers
 * @property Collection<int, Script> $scripts
 * @property Collection<int, StorageProvider> $storageProviders
 * @property Collection<int, StorageProvider> $connectedStorageProviders
 * @property Collection<int, PersonalAccessToken> $tokens
 * @property string $profile_photo_url
 * @property string $timezone
 * @property ?int $current_project_id
 * @property ?Project $currentProject
 * @property Collection<int, Project> $projects
 * @property string $role
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasTimezoneTimestamps;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'current_project_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [];

    /**
     * @return HasMany<Server, covariant $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * @return HasMany<SshKey, covariant $this>
     */
    public function sshKeys(): HasMany
    {
        return $this->hasMany(SshKey::class);
    }

    /**
     * @return HasMany<SourceControl, covariant $this>
     */
    public function sourceControls(): HasMany
    {
        return $this->hasMany(SourceControl::class);
    }

    /**
     * @return HasMany<ServerProvider, covariant $this>
     */
    public function serverProviders(): HasMany
    {
        return $this->hasMany(ServerProvider::class);
    }

    /**
     * @return HasOne<SourceControl, covariant $this>
     */
    public function sourceControl(string $provider): HasOne
    {
        return $this->hasOne(SourceControl::class)->where('provider', $provider);
    }

    /**
     * @return HasMany<StorageProvider, covariant $this>
     */
    public function storageProviders(): HasMany
    {
        return $this->hasMany(StorageProvider::class);
    }

    /**
     * @return HasOne<StorageProvider, covariant $this>
     */
    public function storageProvider(string $provider): HasOne
    {
        return $this->hasOne(StorageProvider::class)->where('provider', $provider);
    }

    /**
     * @return Builder<Project>|BelongsToMany<Project, covariant $this>
     */
    public function allProjects(): Builder|BelongsToMany
    {
        if ($this->isAdmin()) {
            return Project::query();
        }

        return $this->projects();
    }

    /**
     * @return BelongsToMany<Project, covariant $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_project')
            ->withTimestamps();
    }

    /**
     * @return HasOne<Project, covariant $this>
     */
    public function currentProject(): HasOne
    {
        return $this->HasOne(Project::class, 'id', 'current_project_id');
    }

    public function createDefaultProject(): Project
    {
        /** @var ?Project $project */
        $project = $this->projects()->first();

        if (! $project) {
            $project = new Project;
            $project->name = 'default';
            $project->save();

            $project->users()->attach($this->id);
        }

        $this->current_project_id = $project->id;
        $this->save();

        return $project;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * @return HasMany<Script, covariant $this>
     */
    public function scripts(): HasMany
    {
        return $this->hasMany(Script::class);
    }

    /**
     * @return Builder<Server>
     */
    public function allServers(): Builder
    {
        /** @var Builder<Server> $query */
        $query = Server::query();

        return $query->whereHas('project', function (Builder $query): void {
            $query->whereHas('users', function ($query): void {
                $query->where('user_id', $this->id);
            });
        });
    }
}
