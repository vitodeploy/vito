<?php

namespace App\Models;

use App\Enums\WorkerStatus;
use App\Services\ProcessManager\ProcessManager;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @property int $server_id
 * @property int $site_id
 * @property string $command
 * @property string $user
 * @property bool $auto_start
 * @property bool $auto_restart
 * @property int $numprocs
 * @property int $redirect_stderr
 * @property string $stdout_logfile
 * @property string $status
 * @property string $name
 * @property Server $server
 * @property Site $site
 */
class Worker extends AbstractModel
{
    /** @use HasFactory<WorkerFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'site_id',
        'command',
        'user',
        'auto_start',
        'auto_restart',
        'numprocs',
        'redirect_stderr',
        'stdout_logfile',
        'status',
        'name',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'site_id' => 'integer',
        'auto_start' => 'boolean',
        'auto_restart' => 'boolean',
        'numprocs' => 'integer',
        'redirect_stderr' => 'boolean',
    ];

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        WorkerStatus::RUNNING => 'success',
        WorkerStatus::CREATING => 'warning',
        WorkerStatus::DELETING => 'warning',
        WorkerStatus::FAILED => 'danger',
        WorkerStatus::STARTING => 'warning',
        WorkerStatus::STOPPING => 'warning',
        WorkerStatus::RESTARTING => 'warning',
        WorkerStatus::STOPPED => 'gray',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Worker $worker): void {
            try {
                /** @var Service $service */
                $service = $worker->server->processManager();
                /** @var ProcessManager $handler */
                $handler = $service->handler();

                $handler->delete($worker->id, $worker->site_id);
            } catch (Throwable $e) {
                Log::error($e);
            }
        });
    }

    public function getServerIdAttribute(int $value): int
    {
        if ($value === 0) {
            $value = $this->site->server_id;
            $this->fill(['server_id' => $this->site->server_id]);
            $this->save();
        }

        return $value;
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getLogDirectory(): string
    {
        if ($this->user === 'root') {
            return '/root/.logs/workers';
        }

        return '/home/'.$this->user.'/.logs/workers';
    }

    public function getLogFile(): string
    {
        return $this->getLogDirectory().'/'.$this->id.'.log';
    }
}
