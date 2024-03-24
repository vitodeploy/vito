<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
            $user->createDefaultProject();
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

    public function connectedStorageProviders(): HasMany
    {
        return $this->storageProviders()->where('connected', true);
    }

    public function connectedSourceControls(): array
    {
        $connectedSourceControls = [];
        $sourceControls = $this->sourceControls()
            ->where('connected', 1)
            ->get(['provider']);
        foreach ($sourceControls as $sourceControl) {
            $connectedSourceControls[] = $sourceControl->provider;
        }

        return $connectedSourceControls;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function currentProject(): HasOne
    {
        return $this->HasOne(Project::class, 'id', 'current_project_id');
    }

    public function isMemberOfProject(Project $project): bool
    {
        return $project->user_id === $this->id;
    }

    public function createDefaultProject(): Project
    {
        $project = $this->projects()->first();

        if (! $project) {
            $project = new Project();
            $project->user_id = $this->id;
            $project->name = 'Default';
            $project->save();
        }

        $this->current_project_id = $project->id;
        $this->save();

        return $project;
    }
}
