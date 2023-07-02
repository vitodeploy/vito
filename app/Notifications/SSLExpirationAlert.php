<?php

namespace App\Notifications;

use App\Contracts\Notification;
use App\Models\Ssl;

class SSLExpirationAlert implements Notification
{
    protected Ssl $ssl;

    public function __construct(Ssl $ssl)
    {
        $this->ssl = $ssl;
    }

    public function subject(): string
    {
        return __('SSL expiring soon!');
    }

    public function message(bool $mail = false): string
    {
        return $this->ssl->site->domain."'s ".__('SSL is expiring on').' '.$this->ssl->expires_at->format('Y-m-d');
    }
}
