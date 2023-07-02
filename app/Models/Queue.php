<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Jobs\Queue\Deploy;
use App\Jobs\Queue\GetLogs;
use App\Jobs\Queue\Manage;
use App\Jobs\Queue\Remove;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property string $log_directory
 * @property string $log_file
 * @property Server $server
 * @property Site $site
 */
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

    public function getServerIdAttribute(int $value): int
    {
        if (! $value) {
            $value = $this->site->server_id;
            $this->fill(['server_id' => $this->site->server_id]);
            $this->save();
        }

        return $value;
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getLogDirectoryAttribute(): string
    {
        return '/home/'.$this->user.'/.logs/workers';
    }

    public function getLogFileAttribute(): string
    {
        return $this->log_directory.'/'.$this->id.'.log';
    }

    public function deploy(): void
    {
        dispatch(new Deploy($this))->onConnection('ssh');
    }

    private function action(string $type, string $status, string $successStatus, string $failStatus, string $failMessage): void
    {
        $this->status = $status;
        $this->save();
        dispatch(new Manage($this, $type, $successStatus, $failStatus, $failMessage))
            ->onConnection('ssh');
    }

    public function start(): void
    {
        $this->action(
            'start',
            QueueStatus::STARTING,
            QueueStatus::RUNNING,
            QueueStatus::FAILED,
            __('Failed to start')
        );
    }

    public function stop(): void
    {
        $this->action(
            'stop',
            QueueStatus::STOPPING,
            QueueStatus::STOPPED,
            QueueStatus::FAILED,
            __('Failed to stop')
        );
    }

    public function restart(): void
    {
        $this->action(
            'restart',
            QueueStatus::RESTARTING,
            QueueStatus::RUNNING,
            QueueStatus::FAILED,
            __('Failed to restart')
        );
    }

    public function remove(): void
    {
        $this->status = QueueStatus::DELETING;
        $this->save();
        dispatch(new Remove($this))->onConnection('ssh');
    }

    public function getLogs(): void
    {
        dispatch(new GetLogs($this))->onConnection('ssh');
    }
}
