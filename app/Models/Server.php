<?php

namespace App\Models;

use App\Actions\Server\CheckConnection;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\ServerTypes\ServerType;
use App\SSH\Cron\Cron;
use App\SSH\OS\OS;
use App\SSH\Services\Database\Database as DatabaseService;
use App\SSH\Services\Firewall\Firewall as FirewallService;
use App\SSH\Services\PHP\PHP as PHPService;
use App\SSH\Services\ProcessManager\ProcessManager as ProcessManagerService;
use App\SSH\Systemd\Systemd;
use App\Support\Testing\SSHFake;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            DB::beginTransaction();
            try {
                $server->sites()->each(function (Site $site) {
                    $site->delete();
                });
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
                $server->provider()->delete();
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }

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
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<ServerProvider, $this>
     */
    public function serverProvider(): BelongsTo
    {
        return $this->belongsTo(ServerProvider::class, 'provider_id');
    }

    /**
     * @return HasMany<ServerLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    /**
     * @return HasMany<Site, $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Database, $this>
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    /**
     * @return HasMany<DatabaseUser, $this>
     */
    public function databaseUsers(): HasMany
    {
        return $this->hasMany(DatabaseUser::class);
    }

    /**
     * @return HasMany<FirewallRule, $this>
     */
    public function firewallRules(): HasMany
    {
        return $this->hasMany(FirewallRule::class);
    }

    /**
     * @return HasMany<CronJob, $this>
     */
    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    /**
     * @return HasMany<Queue, $this>
     */
    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    /**
     * @return HasMany<Backup, $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * @return HasMany<Queue, $this>
     */
    public function daemons(): HasMany
    {
        return $this->queues()->whereNull('site_id');
    }

    /**
     * @return HasMany<Metric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    /**
     * @return BelongsToMany<SshKey, $this>
     */
    public function sshKeys(): BelongsToMany
    {
        return $this->belongsToMany(SshKey::class, 'server_ssh_keys')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<Tag, $this>
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

    public function service($type, $version = null): ?Service
    {
        /** @var Service $service */
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
        /** @var ?Service $service */
        $service = $this->services()
            ->where('type', $type)
            ->where('is_default', 1)
            ->first();

        // If no default service found, get the first service with status ready or stopped
        if (! $service) {
            /** @ var ?Service $service */
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

    public function ssh(?string $user = null): \App\Helpers\SSH|SSHFake
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

    public function installedNodejsVersions(): array
    {
        $versions = [];
        $nodes = $this->services()->where('type', 'nodejs')->get(['version']);
        foreach ($nodes as $node) {
            $versions[] = $node->version;
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

        return new $providerClass($this->serverProvider, $this);
    }

    public function webserver(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('webserver');
        }

        return $this->service('webserver', $version);
    }

    /**
     * @return Service<DatabaseService>
     */
    public function database(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('database');
        }

        return $this->service('database', $version);
    }

    /**
     * @return Service<FirewallService>
     */
    public function firewall(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('firewall');
        }

        return $this->service('firewall', $version);
    }

    /**
     * @return Service<ProcessManagerService>
     */
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

    public function nodejs(?string $version = null): ?Service
    {
        if (! $version) {
            return $this->defaultService('nodejs');
        }

        return $this->service('nodejs', $version);
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

    public function getAvailableUpdatesAttribute(?int $value): int
    {
        if (! $value) {
            return 0;
        }

        return $value;
    }
}
