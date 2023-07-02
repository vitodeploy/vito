<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SourceControlDisconnected extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $sourceControl;

    public function __construct(string $sourceControl)
    {
        $this->sourceControl = $sourceControl;
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Lost connection to your '.$this->sourceControl)
            ->line("We've lost connection to your $this->sourceControl account.")
            ->line("We'll not able to do any deployments until you reconnect.")
            ->line("To reconnect your $this->sourceControl account please click on the bellow button.")
            ->action('Reconnect', url('/source-controls'));
    }

    public function toArray(): array
    {
        return [
            //
        ];
    }
}
