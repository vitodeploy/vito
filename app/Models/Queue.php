<?php

namespace App\Models;

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

    public function getLogDirectory(): string
    {
        return '/home/'.$this->user.'/.logs/workers';
    }

    public function getLogFile(): string
    {
        return $this->getLogDirectory().'/'.$this->id.'.log';
    }
}
