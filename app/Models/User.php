<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\HasTimezoneTimestamps;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
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

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
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

    protected $appends = [
    ];

    /**
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * @return HasMany<SshKey, $this>
     */
    public function sshKeys(): HasMany
    {
        return $this->hasMany(SshKey::class);
    }

    /**
     * @return HasMany<SourceControl, $this>
     */
    public function sourceControls(): HasMany
    {
        return $this->hasMany(SourceControl::class);
    }

    /**
     * @return HasMany<ServerProvider, $this>
     */
    public function serverProviders(): HasMany
    {
        return $this->hasMany(ServerProvider::class);
    }

    /**
     * @return HasOne<SourceControl, $this>
     */
    public function sourceControl(string $provider): HasOne
    {
        return $this->hasOne(SourceControl::class)->where('provider', $provider);
    }

    /**
     * @return HasMany<StorageProvider, $this>
     */
    public function storageProviders(): HasMany
    {
        return $this->hasMany(StorageProvider::class);
    }

    /**
     * @return HasOne<StorageProvider, $this>
     */
    public function storageProvider(string $provider): HasOne
    {
        return $this->hasOne(StorageProvider::class)->where('provider', $provider);
    }

    public function allProjects(): Builder|BelongsToMany
    {
        if ($this->isAdmin()) {
            return Project::query();
        }

        return $this->projects();
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_project')
            ->withTimestamps();
    }

    /**
     * @return HasOne<Project, $this>
     */
    public function currentProject(): HasOne
    {
        return $this->hasOne(Project::class, 'id', 'current_project_id');
    }

    public function createDefaultProject(): Project
    {
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
     * @return HasMany<Script, $this>
     */
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

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
