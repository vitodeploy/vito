<?php

namespace App\Models;

use App\Actions\Server\CheckConnection;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Exceptions\SSHError;
use App\Facades\SSH;
use App\SSH\OS\Cron;
use App\SSH\OS\OS;
use App\SSH\OS\Systemd;
use App\Support\Testing\SSHFake;
use Carbon\Carbon;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property int $project_id
 * @property int $user_id
 * @property string $name
 * @property string $ssh_user
 * @property string $ip
 * @property ?string $local_ip
 * @property int $port
 * @property string $os
 * @property string $type
 * @property array<string, mixed> $type_data
 * @property string $provider
 * @property int $provider_id
 * @property array<string, mixed> $provider_data
 * @property array<string, mixed> $authentication
 * @property string $public_key
 * @property string $status
 * @property bool $auto_update
 * @property int $available_updates
 * @property int $security_updates
 * @property int|float $progress
 * @property ?string $progress_step
 * @property Project $project
 * @property User $creator
 * @property ServerProvider $serverProvider
 * @property Collection<int, ServerLog> $logs
 * @property Collection<int, Site> $sites
 * @property Collection<int, Service> $services
 * @property Collection<int, Database> $databases
 * @property Collection<int, DatabaseUser> $databaseUsers
 * @property Collection<int, FirewallRule> $firewallRules
 * @property Collection<int, CronJob> $cronJobs
 * @property Collection<int, Worker> $queues
 * @property Collection<int, Backup> $backups
 * @property Collection<int, SshKey> $sshKeys
 * @property Collection<int, Tag> $tags
 * @property string $hostname
 * @property int $updates
 * @property ?Carbon $last_update_check
 */
class Server extends AbstractModel
{
    /** @use HasFactory<ServerFactory> */
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
        'port' => 'integer',
        'provider_data' => 'json',
        'authentication' => 'encrypted:json',
        'auto_update' => 'boolean',
        'available_updates' => 'integer',
        'security_updates' => 'integer',
        'progress' => 'float',
        'updates' => 'integer',
        'last_update_check' => 'datetime',
    ];

    protected $hidden = [
        'authentication',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Server $server): void {
            DB::beginTransaction();
            try {
                $server->sites()->each(function ($site): void {
                    /** @var Site $site */
                    $site->workers()->delete();
                    $site->ssls()->delete();
                    $site->deployments()->delete();
                    $site->deploymentScript()->delete();
                });
                $server->sites()->delete();
                $server->logs()->each(function ($log): void {
                    /** @var ServerLog $log */
                    $log->delete();
                });
                $server->services()->delete();
                $server->databases()->delete();
                $server->databaseUsers()->delete();
                $server->firewallRules()->delete();
                $server->cronJobs()->delete();
                $server->workers()->delete();
                $server->daemons()->delete();
                $server->sshKeys()->detach();
                if (File::exists($server->sshKey()['public_key_path'])) {
                    File::delete($server->sshKey()['public_key_path']);
                }
                if (File::exists($server->sshKey()['private_key_path'])) {
                    File::delete($server->sshKey()['private_key_path']);
                }
                $server->provider()->delete();
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        ServerStatus::READY => 'success',
        ServerStatus::INSTALLING => 'warning',
        ServerStatus::DISCONNECTED => 'gray',
        ServerStatus::INSTALLATION_FAILED => 'danger',
        ServerStatus::UPDATING => 'warning',
    ];

    public function isReady(): bool
    {
        return $this->status === ServerStatus::READY;
    }

    public function isInstalling(): bool
    {
        return in_array($this->status, [ServerStatus::INSTALLING, ServerStatus::INSTALLATION_FAILED]);
    }

    public function isInstallationFailed(): bool
    {
        return $this->status === ServerStatus::INSTALLATION_FAILED;
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<ServerProvider, covariant $this>
     */
    public function serverProvider(): BelongsTo
    {
        return $this->belongsTo(ServerProvider::class, 'provider_id');
    }

    /**
     * @return HasMany<ServerLog, covariant $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    /**
     * @return HasMany<Site, covariant $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * @return HasMany<Service, covariant $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Database, covariant $this>
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    /**
     * @return HasMany<DatabaseUser, covariant $this>
     */
    public function databaseUsers(): HasMany
    {
        return $this->hasMany(DatabaseUser::class);
    }

    /**
     * @return HasMany<FirewallRule, covariant $this>
     */
    public function firewallRules(): HasMany
    {
        return $this->hasMany(FirewallRule::class);
    }

    /**
     * @return HasMany<CronJob, covariant $this>
     */
    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    /**
     * @return HasMany<Worker, covariant $this>
     */
    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    /**
     * @return HasMany<Backup, covariant $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * @return HasMany<Worker, covariant $this>
     */
    public function daemons(): HasMany
    {
        return $this->workers()->whereNull('site_id');
    }

    /**
     * @return HasMany<Metric, covariant $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    /**
     * @return BelongsToMany<SshKey, covariant $this>
     */
    public function sshKeys(): BelongsToMany
    {
        return $this->belongsToMany(SshKey::class, 'server_ssh_keys')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<Tag, covariant $this>
     */
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

    /**
     * @return array<string>
     */
    public function getSshUsers(): array
    {
        $users = ['root', $this->getSshUser()];
        $isolatedSites = $this->sites()->pluck('user')->toArray();
        $users = array_merge($users, $isolatedSites);

        return array_unique($users);
    }

    public function service(string $type, mixed $version = null): ?Service
    {
        /** @var ?Service $service */
        $service = $this->services()
            ->where(function ($query) use ($type, $version): void {
                $query->where('type', $type);
                if ($version) {
                    $query->where('version', $version);
                }
            })
            ->first();

        return $service;
    }

    public function defaultService(string $type): ?Service
    {
        /** @var ?Service $service */
        $service = $this->services()
            ->where('type', $type)
            ->where('is_default', 1)
            ->first();

        // If no default service found, get the first service with status ready or stopped
        if (! $service) {
            /** @var ?Service $service */
            $service = $this->services()
                ->where('type', $type)
                ->whereIn('status', [ServiceStatus::READY, ServiceStatus::STOPPED])
                ->first();
            if ($service) {
                $service->is_default = true;
                $service->save();
            }
        }

        return $service;
    }

    public function ssh(?string $user = null): \App\Helpers\SSH|SSHFake
    {
        return SSH::init($this, $user);
    }

    /**
     * @return array<int, string>
     */
    public function installedPHPVersions(): array
    {
        $versions = [];
        $phps = $this->services()->where('type', 'php')->get(['version']);
        /** @var Service $php */
        foreach ($phps as $php) {
            $versions[] = $php->version;
        }

        return $versions;
    }

    /**
     * @return array<int, string>
     */
    public function installedNodejsVersions(): array
    {
        $versions = [];
        $nodes = $this->services()->where('type', 'nodejs')->get(['version']);
        /** @var Service $node */
        foreach ($nodes as $node) {
            $versions[] = $node->version;
        }

        return $versions;
    }

    public function provider(): \App\ServerProviders\ServerProvider
    {
        $providerClass = config('server-provider.providers.'.$this->provider.'.handler');

        /** @var \App\ServerProviders\ServerProvider $provider */
        $provider = new $providerClass($this->serverProvider ?? new ServerProvider, $this);

        return $provider;
    }

    public function webserver(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('webserver');
        }

        return $this->service('webserver', $version);
    }

    public function database(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('database');
        }

        return $this->service('database', $version);
    }

    public function firewall(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('firewall');
        }

        return $this->service('firewall', $version);
    }

    public function processManager(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('process_manager');
        }

        return $this->service('process_manager', $version);
    }

    public function php(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('php');
        }

        return $this->service('php', $version);
    }

    public function nodejs(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('nodejs');
        }

        return $this->service('nodejs', $version);
    }

    public function memoryDatabase(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('memory_database');
        }

        return $this->service('memory_database', $version);
    }

    public function monitoring(?string $version = null): ?Service
    {
        if ($version === null || $version === '' || $version === '0') {
            return $this->defaultService('monitoring');
        }

        return $this->service('monitoring', $version);
    }

    /**
     * @return array<string, string>
     */
    public function sshKey(): array
    {
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));

        return [
            'public_key' => str(Storage::disk(config('core.key_pairs_disk'))->get($this->id.'.pub'))->replace("\n", '')->toString(),
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

    /**
     * @throws SSHError
     */
    public function checkForUpdates(): void
    {
        $this->updates = $this->os()->availableUpdates();
        $this->last_update_check = now();
        $this->save();
    }

    public function getAvailableUpdatesAttribute(?int $value): int
    {
        if ($value === null || $value === 0) {
            return 0;
        }

        return $value;
    }

    /**
     * @throws Throwable
     */
    public function download(string $path, string $disk = 'tmp'): void
    {
        $this->ssh()->download(
            Storage::disk($disk)->path(basename($path)),
            $path
        );
    }

    public function getStatusColor(): string
    {
        if (isset(self::$statusColors[$this->status])) {
            return self::$statusColors[$this->status];
        }

        return 'gray';
    }
}
