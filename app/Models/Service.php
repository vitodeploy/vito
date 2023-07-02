<?php

namespace App\Models;

use App\Contracts\Database;
use App\Contracts\Firewall;
use App\Contracts\ProcessManager;
use App\Contracts\Webserver;
use App\Enums\ServiceStatus;
use App\Events\Broadcast;
use App\Exceptions\InstallationFailed;
use App\Jobs\Service\Manage;
use App\ServiceHandlers\PHP;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

/**
 * @property int $server_id
 * @property string $type
 * @property array $type_data
 * @property string $name
 * @property string $version
 * @property string $unit
 * @property string $logs
 * @property string $status
 * @property bool $is_default
 * @property Server $server
 */
class Service extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'type',
        'type_data',
        'name',
        'version',
        'unit',
        'logs',
        'status',
        'is_default',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'type_data' => 'json',
        'is_default' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function handler(): Database|Firewall|Webserver|PHP|ProcessManager
    {
        $handler = config('core.service_handlers')[$this->name];

        return new $handler($this);
    }

    public function installer(): mixed
    {
        $installer = config('core.service_installers')[$this->name];

        return new $installer($this);
    }

    public function uninstaller(): mixed
    {
        $uninstaller = config('core.service_uninstallers')[$this->name];

        return new $uninstaller($this);
    }

    public function getUnitAttribute($value): string
    {
        if ($value) {
            return $value;
        }
        if (isset(config('core.service_units')[$this->name])) {
            $value = config('core.service_units')[$this->name][$this->server->os][$this->version];
            if ($value) {
                $this->fill(['unit' => $value]);
                $this->save();
            }
        }

        return $value;
    }

    public function install(): void
    {
        Bus::chain([
            $this->installer(),
            function () {
                event(
                    new Broadcast('install-service-finished', [
                        'service' => $this,
                    ])
                );
            },
        ])->catch(function () {
            event(
                new Broadcast('install-service-failed', [
                    'service' => $this,
                ])
            );
        })->onConnection('ssh-long')->dispatch();
    }

    /**
     * @throws InstallationFailed
     */
    public function validateInstall($result): void
    {
        if (Str::contains($result, 'Active: active')) {
            event(
                new Broadcast('install-service-finished', [
                    'service' => $this,
                ])
            );
        } else {
            event(
                new Broadcast('install-service-failed', [
                    'service' => $this,
                ])
            );
            throw new InstallationFailed();
        }
    }

    public function uninstall(): void
    {
        $this->status = ServiceStatus::UNINSTALLING;
        $this->save();
        Bus::chain([
            $this->uninstaller(),
            function () {
                event(
                    new Broadcast('uninstall-service-finished', [
                        'service' => $this,
                    ])
                );
                $this->delete();
            },
        ])->catch(function () {
            $this->status = ServiceStatus::FAILED;
            $this->save();
            event(
                new Broadcast('uninstall-service-failed', [
                    'service' => $this,
                ])
            );
        })->onConnection('ssh')->dispatch();
    }

    public function start(): void
    {
        $this->action(
            'start',
            ServiceStatus::STARTING,
            ServiceStatus::READY,
            ServiceStatus::STOPPED,
            __('Failed to start')
        );
    }

    public function stop(): void
    {
        $this->action(
            'stop',
            ServiceStatus::STOPPING,
            ServiceStatus::STOPPED,
            ServiceStatus::FAILED,
            __('Failed to stop')
        );
    }

    public function restart(): void
    {
        $this->action(
            'restart',
            ServiceStatus::RESTARTING,
            ServiceStatus::READY,
            ServiceStatus::FAILED,
            __('Failed to restart')
        );
    }

    public function action(
        string $type,
        string $status,
        string $successStatus,
        string $failStatus,
        string $failMessage
    ): void {
        $this->status = $status;
        $this->save();
        dispatch(new Manage($this, $type, $successStatus, $failStatus, $failMessage))
            ->onConnection('ssh');
    }

    public function installedVersions(): array
    {
        $versions = [];
        $services = $this->server->services()->where('type', $this->type)->get(['version']);
        foreach ($services as $service) {
            $versions[] = $service->version;
        }

        return $versions;
    }
}
