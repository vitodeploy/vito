<?php

namespace App\Models;

use App\Actions\Service\Manage;
use App\Enums\ServiceStatus;
use App\Exceptions\ServiceInstallationFailed;
use App\Services\Database\Database;
use App\Services\Firewall\Firewall;
use App\Services\PHP\PHP;
use App\Services\ProcessManager\ProcessManager;
use App\Services\ServiceInterface;
use App\Services\Webserver\Webserver;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @property int $server_id
 * @property string $type
 * @property array<string, mixed> $type_data
 * @property string $name
 * @property string $version
 * @property string $installed_version
 * @property string $unit
 * @property string $logs
 * @property ServiceStatus $status
 * @property bool $is_default
 * @property Server $server
 */
class Service extends AbstractModel
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'type',
        'type_data',
        'name',
        'version',
        'installed_version',
        'unit',
        'logs',
        'status',
        'is_default',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'type_data' => 'json',
        'is_default' => 'boolean',
        'status' => ServiceStatus::class,
    ];

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function handler(): ServiceInterface|Webserver|PHP|Firewall|Database|ProcessManager
    {
        $name = $this->name;
        $handler = config("service.services.$name.handler");

        if (! $handler) {
            throw new InvalidArgumentException("Service handler for $name is not defined.");
        }

        /** @var ServiceInterface $service */
        $service = new $handler($this);

        return $service;
    }

    /**
     * @throws ServiceInstallationFailed
     */
    public function validateInstall(string $result): void
    {
        if (! Str::contains($result, 'Active: active')) {
            throw new ServiceInstallationFailed;
        }
    }

    public function start(): void
    {
        $this->handler()->unit() && app(Manage::class)->start($this);
    }

    public function stop(): void
    {
        $this->handler()->unit() && app(Manage::class)->stop($this);
    }

    public function restart(): void
    {
        $this->handler()->unit() && app(Manage::class)->restart($this);
    }

    public function reload(): void
    {
        $this->handler()->unit() && app(Manage::class)->reload($this);
    }

    public function enable(): void
    {
        $this->handler()->unit() && app(Manage::class)->enable($this);
    }

    public function disable(): void
    {
        $this->handler()->unit() && app(Manage::class)->disable($this);
    }
}
