<?php

namespace App\Models;

use App\Actions\Server\CheckConnection;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\ServerTypes\ServerType;
use App\SSH\Cron\Cron;
use App\SSH\OS\OS;
use App\SSH\Systemd\Systemd;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $project_id
 * @property int $user_id
 * @property string $name
 * @property string $ssh_user
 * @property string $ip
 * @property string $local_ip
 * @property int $port
 * @property string $os
 * @property string $type
 * @property array $type_data
 * @property string $provider
 * @property int $provider_id
 * @property array $provider_data
 * @property array $authentication
 * @property string $public_key
 * @property string $status
 * @property bool $auto_update
 * @property int $available_updates
 * @property int $security_updates
 * @property int $progress
 * @property string $progress_step
 * @property Project $project
 * @property User $creator
 * @property ServerProvider $serverProvider
 * @property ServerLog[] $logs
 * @property Site[] $sites
 * @property Service[] $services
 * @property Database[] $databases
 * @property DatabaseUser[] $databaseUsers
 * @property FirewallRule[] $firewallRules
 * @property CronJob[] $cronJobs
 * @property Queue[] $queues
 * @property Backup[] $backups
 * @property Queue[] $daemons
 * @property SshKey[] $sshKeys
 * @property string $hostname
 * @property int $updates
 * @property Carbon $last_update_check
 */
class Server extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'name',
        'ssh_user',
        'ip',
        'local_ip',
        'port',
        'os',
        'type',
        'type_data',
        'provider',
        'provider_id',
        'provider_data',
        'authentication',
        'public_key',
        'status',
        'auto_update',
        'available_updates',
        'security_updates',
        'progress',
        'progress_step',
        'updates',
        'last_update_check',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'user_id' => 'integer',
        'type_data' => 'json',
        'port' => 'integer',
        'provider_data' => 'json',
        'authentication' => 'encrypted:json',
        'auto_update' => 'boolean',
        'available_updates' => 'integer',
        'security_updates' => 'integer',
        'progress' => 'integer',
        'updates' => 'integer',
        'last_update_check' => 'datetime',
    ];

    protected $hidden = [
        'authentication',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Server $server) {
            $server->sites()->each(function (Site $site) {
                $site->delete();
            });
            $server->provider()->delete();
            $server->logs()->each(function (ServerLog $log) {
                $log->delete();
            });
            $server->services()->delete();
            $server->databases()->delete();
            $server->databaseUsers()->delete();
            $server->firewallRules()->delete();
            $server->cronJobs()->delete();
            $server->queues()->delete();
            $server->daemons()->delete();
            $server->sshKeys()->detach();
            if (File::exists($server->sshKey()['public_key_path'])) {
                File::delete($server->sshKey()['public_key_path']);
            }
            if (File::exists($server->sshKey()['private_key_path'])) {
                File::delete($server->sshKey()['private_key_path']);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function serverProvider(): BelongsTo
    {
        return $this->belongsTo(ServerProvider::class, 'provider_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function databaseUsers(): HasMany
    {
        return $this->hasMany(DatabaseUser::class);
    }

    public function firewallRules(): HasMany
    {
        return $this->hasMany(FirewallRule::class);
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function daemons(): HasMany
    {
        return $this->queues()->whereNull('site_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function sshKeys(): BelongsToMany
    {
        return $this->belongsToMany(SshKey::class, 'server_ssh_keys')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getSshUser(): string
    {
        if ($this->ssh_user) {
            return $this->ssh_user;
        }

        return config('core.ssh_user');
    }

    public function service($type, $version = null): ?Service
    {
        /* @var Service $service */
        $service = $this->services()
            ->where(function ($query) use ($type, $version) {
                $query->where('type', $type);
                if ($version) {
                    $query->where('version', $version);
                }
            })
            ->first();

        return $service;
    }

    public function defaultService($type): ?Service
    {
        /* @var Service $service */
        $service = $this->services()
            ->where('type', $type)
            ->where('is_default', 1)
            ->first();

        // If no default service found, get the first service with status ready or stopped
        if (! $service) {
            /** @var Service $service */
            $service = $this->services()
                ->where('type', $type)
                ->whereIn('status', [ServiceStatus::READY, ServiceStatus::STOPPED])
                ->first();
            if ($service) {
                $service->is_default = 1;
                $service->save();
            }
        }

        return $service;
    }

    public function ssh(?string $user = null): mixed
    {
        return SSH::init($this, $user);
    }

    public function installedPHPVersions(): array
    {
        $versions = [];
        $phps = $this->services()->where('type', 'php')->get(['version']);
        foreach ($phps as $php) {
            $versions[] = $php->version;
        }

        return $versions;
    }

    public function type(): ServerType
    {
        $typeClass = config('core.server_types_class')[$this->type];

        return new $typeClass($this);
    }

    public function provider(): \App\ServerProviders\ServerProvider
    {
        $providerClass = config('core.server_providers_class')[$this->provider];

        return new $providerClass($this);
    }

    public function webserver(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('webserver');
        }

        return $this->service('webserver', $version);
    }

    public function database(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('database');
        }

        return $this->service('database', $version);
    }

    public function firewall(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('firewall');
        }

        return $this->service('firewall', $version);
    }

    public function processManager(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('process_manager');
        }

        return $this->service('process_manager', $version);
    }

    public function php(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('php');
        }

        return $this->service('php', $version);
    }

    public function memoryDatabase(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('memory_database');
        }

        return $this->service('memory_database', $version);
    }

    public function monitoring(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('monitoring');
        }

        return $this->service('monitoring', $version);
    }

    public function sshKey(): array
    {
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));

        return [
            'public_key' => Str::replace("\n", '', Storage::disk(config('core.key_pairs_disk'))->get($this->id.'.pub')),
            'public_key_path' => $storageDisk->path($this->id.'.pub'),
            'private_key_path' => $storageDisk->path((string) $this->id),
        ];
    }

    public function checkConnection(): self
    {
        return app(CheckConnection::class)->check($this);
    }

    public function hostname(): string
    {
        return Str::of($this->name)->slug();
    }

    public function os(): OS
    {
        return new OS($this);
    }

    public function systemd(): Systemd
    {
        return new Systemd($this);
    }

    public function cron(): Cron
    {
        return new Cron($this);
    }

    public function checkForUpdates(): void
    {
        $this->updates = $this->os()->availableUpdates();
        $this->last_update_check = now();
        $this->save();
    }
}
