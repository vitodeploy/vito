<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $profile_photo_path
 * @property string $two_factor_recovery_codes
 * @property string $two_factor_secret
 * @property SshKey[] $sshKeys
 * @property SourceControl[] $sourceControls
 * @property ServerProvider[] $serverProviders
 * @property Script[] $scripts
 * @property StorageProvider[] $storageProviders
 * @property StorageProvider[] $connectedStorageProviders
 * @property Collection $tokens
 * @property string $profile_photo_url
 * @property string $timezone
 * @property int $current_project_id
 * @property Project $currentProject
 * @property Collection<Project> $projects
 * @property string $role
 */
class User extends Authenticatable
{
    use HasFactory;
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

    protected $appends = [
    ];

    public static function boot(): void
    {
        parent::boot();

        static::created(function (User $user) {
            if (Project::count() === 0) {
                $user->createDefaultProject();
            }
        });
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function sshKeys(): HasMany
    {
        return $this->hasMany(SshKey::class);
    }

    public function sourceControls(): HasMany
    {
        return $this->hasMany(SourceControl::class);
    }

    public function serverProviders(): HasMany
    {
        return $this->hasMany(ServerProvider::class);
    }

    public function sourceControl(string $provider): HasOne
    {
        return $this->hasOne(SourceControl::class)->where('provider', $provider);
    }

    public function storageProviders(): HasMany
    {
        return $this->hasMany(StorageProvider::class);
    }

    public function storageProvider(string $provider): HasOne
    {
        return $this->hasOne(StorageProvider::class)->where('provider', $provider);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_project')->withTimestamps();
    }

    public function currentProject(): HasOne
    {
        return $this->HasOne(Project::class, 'id', 'current_project_id');
    }

    public function createDefaultProject(): Project
    {
        $project = $this->projects()->first();

        if (! $project) {
            $project = new Project();
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

    public function scripts(): HasMany
    {
        return $this->hasMany(Script::class);
    }

    public function allServers(): Builder
    {
        return Server::query()->whereHas('project', function (Builder $query) {
            $query->whereHas('users', function ($query) {
                $query->where('user_id', $this->id);
            });
        });
    }
}
