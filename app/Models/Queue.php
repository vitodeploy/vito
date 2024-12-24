<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends AbstractModel
{
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
    ];

    protected $casts = [
        'server_id' => 'integer',
        'site_id' => 'integer',
        'auto_start' => 'boolean',
        'auto_restart' => 'boolean',
        'numprocs' => 'integer',
        'redirect_stderr' => 'boolean',
    ];

    public static array $statusColors = [
        QueueStatus::RUNNING => 'success',
        QueueStatus::CREATING => 'warning',
        QueueStatus::DELETING => 'warning',
        QueueStatus::FAILED => 'danger',
        QueueStatus::STARTING => 'warning',
        QueueStatus::STOPPING => 'warning',
        QueueStatus::RESTARTING => 'warning',
        QueueStatus::STOPPED => 'gray',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Queue $queue) {
            $queue->server->processManager()->handler()->delete($queue->id, $queue->site_id);
        });
    }

    public function getServerIdAttribute(int $value): int
    {
        if (! $value) {
            $value = $this->site->server_id;
            $this->fill(['server_id' => $this->site->server_id]);
            $this->save();
        }

        return $value;
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getLogDirectory(): string
    {
        return '/home/'.$this->user.'/.logs/workers';
    }

    public function getLogFile(): string
    {
        return $this->getLogDirectory().'/'.$this->id.'.log';
    }
}
