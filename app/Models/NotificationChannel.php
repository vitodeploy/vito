<?php

namespace App\Models;

use App\Notifications\NotificationInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string provider
 * @property array data
 * @property string label
 * @property bool connected
 * @property int $project_id
 */
class NotificationChannel extends AbstractModel
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'provider',
        'label',
        'data',
        'connected',
        'is_default',
        'project_id',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'data' => 'array',
        'connected' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function provider(): \App\NotificationChannels\NotificationChannel
    {
        $class = config('core.notification_channels_providers_class')[$this->provider];

        return new $class($this);
    }

    public static function notifyAll(NotificationInterface $notification): void
    {
        $channels = self::all();
        foreach ($channels as $channel) {
            $channel->notify($notification);
        }
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function getByProjectId(int $projectId): Builder
    {
        return self::query()
            ->where('project_id', $projectId)
            ->orWhereNull('project_id');
    }
}
