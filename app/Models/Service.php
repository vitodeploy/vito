<?php

namespace App\Models;

use App\Actions\Service\Manage;
use App\Exceptions\ServiceInstallationFailed;
use App\SSH\Services\ServiceInterface;
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

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (Service $service) {
            if (array_key_exists($service->name, config('core.service_units'))) {
                $service->unit = config('core.service_units')[$service->name][$service->server->os][$service->version];
            }
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function handler(): ServiceInterface
    {
        $handler = config('core.service_handlers')[$this->name];

        return new $handler($this);
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
        $this->unit && app(Manage::class)->start($this);
    }

    public function stop(): void
    {
        $this->unit && app(Manage::class)->stop($this);
    }

    public function restart(): void
    {
        $this->unit && app(Manage::class)->restart($this);
    }

    public function enable(): void
    {
        $this->unit && app(Manage::class)->enable($this);
    }

    public function disable(): void
    {
        $this->unit && app(Manage::class)->disable($this);
    }
}
