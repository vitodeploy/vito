<?php

namespace App\Models;

use App\ApplicationTypes\ApplicationType;
use App\Enums\ApplicationStatus;
use App\Enums\SslStatus;
use App\Services\Webserver\Webserver;
use App\Traits\HasProjectThroughServer;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * @property int $server_id
 * @property string $type
 * @property array<string, mixed> $type_data
 * @property string $domain
 * @property array<int, string> $aliases
 * @property ApplicationStatus $status
 * @property ?string $custom_template
 * @property bool $force_ssl
 * @property Server $server
 * @property Collection<int, Ssl> $ssls
 * @property ?Ssl $activeSsl
 * @property Collection<int, ServerLog> $logs
 * @property Collection<int, Deployment> $deployments
 */
class Application extends AbstractModel
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    use HasProjectThroughServer;

    protected $fillable = [
        'server_id',
        'type',
        'type_data',
        'domain',
        'aliases',
        'status',
        'custom_template',
        'force_ssl',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'type_data' => 'json',
        'aliases' => 'array',
        'force_ssl' => 'boolean',
        'status' => ApplicationStatus::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Application $application): void {
            $application->ssls()->delete();
            $application->deployments()->delete();
            $application->logs()->delete();
        });
    }

    public function isReady(): bool
    {
        return $this->status === ApplicationStatus::READY;
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<ServerLog, covariant $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    /**
     * @return HasMany<Deployment, covariant $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return HasMany<Ssl, covariant $this>
     */
    public function ssls(): HasMany
    {
        return $this->hasMany(Ssl::class);
    }

    /**
     * @return HasOne<Ssl, covariant $this>
     */
    public function activeSsl(): HasOne
    {
        return $this->hasOne(Ssl::class)
            ->where('expires_at', '>=', now())
            ->where('status', SslStatus::CREATED)
            ->where('is_active', true)
            ->orderByDesc('id');
    }

    public function type(): ApplicationType
    {
        $handlerClass = config('application.types.'.$this->type.'.handler');
        if (! class_exists($handlerClass)) {
            throw new RuntimeException("Application type handler class {$handlerClass} does not exist.");
        }

        /** @var ApplicationType $handler */
        $handler = new $handlerClass($this);

        return $handler;
    }

    public function getUrl(): string
    {
        if ($this->activeSsl) {
            return 'https://'.$this->domain;
        }

        return 'http://'.$this->domain;
    }

    public function getAliasesString(): string
    {
        if (! empty($this->aliases)) {
            return implode(' ', $this->aliases);
        }

        return '';
    }

    public function webserver(): Webserver
    {
        /** @var Service $webserverService */
        $webserverService = $this->server->webserver();

        /** @var Webserver $handler */
        $handler = $webserverService->handler();

        return $handler;
    }

    public function webserverId(): string
    {
        return $this->webserver()::id();
    }

    public function refreshVhost(): void
    {
        app(\App\Actions\Application\DeployApplication::class)->deploy($this);
    }
}
