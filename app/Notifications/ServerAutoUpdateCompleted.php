<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class ServerAutoUpdateCompleted extends AbstractNotification
{
    public function __construct(
        protected Server $server,
        protected int $upgraded,
        protected int $kernelUpdatesRemaining,
        protected bool $rebootRequired,
    ) {}

    public function rawText(): string
    {
        $text = __('Automatic update completed for server [:server]. Packages upgraded: :count', [
            'server' => $this->server->name,
            'count' => $this->upgraded,
        ]);

        if ($this->kernelUpdatesRemaining > 0) {
            $text .= "\n".__('Kernel updates available: :count (not applied automatically)', [
                'count' => $this->kernelUpdatesRemaining,
            ]);
        }

        if ($this->rebootRequired) {
            $text .= "\n".__('This server needs to be rebooted to finish applying updates.');
        }

        return $text;
    }

    public function toEmail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->success()
            ->subject(__('Automatic update completed'))
            ->line(__('Vito automatically applied package updates to your server [:server].', [
                'server' => $this->server->name,
            ]))
            ->line(__('Packages upgraded: :count', ['count' => $this->upgraded]));

        if ($this->kernelUpdatesRemaining > 0) {
            $message
                ->line(__('Kernel updates available: :count', ['count' => $this->kernelUpdatesRemaining]))
                ->line(__('Kernel updates are not applied automatically — you can apply them from the server\'s page (this requires a reboot).'));
        }

        if ($this->rebootRequired) {
            $message->line(__('This server needs to be rebooted to finish applying updates.'));
        }

        return $message->action(__('View server'), url('/servers/'.$this->server->id));
    }
}
