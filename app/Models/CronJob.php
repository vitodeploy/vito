<?php

namespace App\Models;

use App\Enums\CronjobStatus;
use App\Jobs\CronJob\AddToServer;
use App\Jobs\CronJob\RemoveFromServer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $command
 * @property string $user
 * @property string $frequency
 * @property string $frequency_label
 * @property bool $hidden
 * @property string $status
 * @property string $crontab
 * @property Server $server
 */
class CronJob extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'command',
        'user',
        'frequency',
        'hidden',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'hidden' => 'boolean',
    ];

    protected $appends = [
        'frequency_label',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getCrontabAttribute(): string
    {
        $data = '';
        $cronJobs = $this->server->cronJobs()->where('user', $this->user)->get();
        foreach ($cronJobs as $key => $cronJob) {
            $data .= $cronJob->frequency.' '.$cronJob->command;
            if ($key != count($cronJobs) - 1) {
                $data .= "\n";
            }
        }

        return $data;
    }

    public function addToServer(): void
    {
        dispatch(new AddToServer($this))->onConnection('ssh');
    }

    public function removeFromServer(): void
    {
        $this->status = CronjobStatus::DELETING;
        $this->save();
        dispatch(new RemoveFromServer($this))->onConnection('ssh');
    }

    public function getFrequencyLabelAttribute(): string
    {
        $labels = [
            '* * * * *' => 'Every minute',
            '0 * * * *' => 'Hourly',
            '0 0 * * *' => 'Daily',
            '0 0 * * 0' => 'Weekly',
            '0 0 1 * *' => 'Monthly',
        ];
        if (isset($labels[$this->frequency])) {
            return $labels[$this->frequency];
        }

        return $this->frequency;
    }
}
