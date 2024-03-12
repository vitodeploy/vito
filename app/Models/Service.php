<?php

namespace App\Models;

use App\Actions\Service\Manage;
use App\Exceptions\ServiceInstallationFailed;
use App\SSH\Services\Firewall\Firewall;
use App\SSH\Services\PHP\PHP;
use App\SSH\Services\ProcessManager\ProcessManager;
use App\SSH\Services\Redis\Redis;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * @return PHP
     * @return Webserver
     * @return \App\SSH\Services\Database\Database
     * @return Firewall
     * @return ProcessManager
     * @return Redis
     */
    public function handler(): mixed
    {
        $handler = config('core.service_handlers')[$this->name];

        return new $handler($this);
    }

    public function getUnitAttribute($value): ?string
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

    /**
     * @throws ServiceInstallationFailed
     */
    public function validateInstall($result): void
    {
        if (! Str::contains($result, 'Active: active')) {
            throw new ServiceInstallationFailed();
        }
    }

    public function start(): void
    {
        app(Manage::class)->start($this);
    }

    public function stop(): void
    {
        app(Manage::class)->stop($this);
    }

    public function restart(): void
    {
        app(Manage::class)->restart($this);
    }
}
