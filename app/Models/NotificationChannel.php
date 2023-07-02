<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $provider
 * @property string $label
 * @property array $data
 * @property bool $connected
 * @property bool $is_default
 * @property User $user
 */
class NotificationChannel extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'label',
        'data',
        'connected',
        'is_default',
    ];

    protected $casts = [
        'data' => 'json',
        'connected' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function provider(): \App\Contracts\NotificationChannel
    {
        $provider = config('core.notification_channels_providers_class')[$this->provider];

        return new $provider($this);
    }
}
