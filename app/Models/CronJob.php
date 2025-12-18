<?php

namespace App\Models;

use App\Enums\CronjobStatus;
use Database\Factories\CronJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property ?int $site_id
 * @property string $command
 * @property string $user
 * @property string $frequency
 * @property bool $hidden
 * @property CronjobStatus $status
 * @property string $crontab
 * @property Server $server
 * @property ?Site $site
 */
class CronJob extends AbstractModel
{
    /** @use HasFactory<CronJobFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'site_id',
        'command',
        'user',
        'frequency',
        'hidden',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'site_id' => 'integer',
        'hidden' => 'boolean',
        'status' => CronjobStatus::class,
    ];

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

    public function getServerIdAttribute(?int $value): ?int
    {
        if ($value === 0 && $this->site) {
            $value = $this->site->server_id;
            $this->fill(['server_id' => $this->site->server_id]);
            $this->save();
        }

        return $value;
    }

    public static function crontab(Server $server, string $user): string
    {
        $data = '';
        $cronJobs = $server->cronJobs()
            ->where('user', $user)
            ->whereIn('status', [
                CronjobStatus::READY,
                CronjobStatus::CREATING,
                CronjobStatus::UPDATING,
                CronjobStatus::ENABLING,
            ])
            ->get();
        /** @var CronJob $cronJob */
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

        return $labels[$this->frequency] ?? $this->frequency;
    }

    public function isEnabled(): bool
    {
        return $this->status === CronjobStatus::READY;
    }

    public function isDisabled(): bool
    {
        return $this->status === CronjobStatus::DISABLED;
    }
}
