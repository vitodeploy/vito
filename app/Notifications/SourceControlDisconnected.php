<?php

namespace App\Notifications;

use App\Models\SourceControl;
use Illuminate\Notifications\Messages\MailMessage;

class SourceControlDisconnected extends AbstractNotification
{
    public function __construct(protected SourceControl $sourceControl) {}

    public function rawText(): string
    {
        return __('Source control [:sourceControl] has been disconnected from Vito', [
            'sourceControl' => $this->sourceControl->profile,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Source control disconnected!'))
            ->line($this->rawText());
    }
}
