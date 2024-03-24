<?php

namespace App\Models;

use App\Notifications\NotificationInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string provider
 * @property array data
 * @property string label
 * @property bool connected
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
}
