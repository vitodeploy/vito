<?php

namespace App\Models;

use App\Enums\CronjobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $command
 * @property string $user
 * @property string $frequency
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

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function crontab(Server $server, string $user): string
    {
        $data = '';
        $cronJobs = $server->cronJobs()
            ->where('user', $user)
            ->whereIn('status', [
                CronjobStatus::READY,
                CronjobStatus::CREATING,
                CronjobStatus::ENABLING,
            ])
            ->get();
        foreach ($cronJobs as $key => $cronJob) {
            $data .= $cronJob->frequency.' '.$cronJob->command;
            if ($key != count($cronJobs) - 1) {
                $data .= "\n";
            }
        }

        return $data;
    }

    public function frequencyLabel(): string
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
