<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Notifications\Messages\MailMessage;

class BackupFailed extends AbstractNotification
{
    public function __construct(protected Backup $backup) {}

    public function rawText(): string
    {
        return __("A :type backup on server [:server] failed.\nCheck the backup logs for details.\n:link", [
            'type' => $this->backup->type->getText(),
            'server' => $this->backup->server->name,
            'link' => url('/servers/'.$this->backup->server_id.'/backups/'.$this->backup->id),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Backup failed'))
            ->line(__('A :type backup on your server [:server] failed to complete.', [
                'type' => $this->backup->type->getText(),
                'server' => $this->backup->server->name,
            ]))
            ->line(__('Your data was not backed up. Check the backup logs to find out what went wrong.'))
            ->action(__('View backup'), url('/servers/'.$this->backup->server_id.'/backups/'.$this->backup->id));
    }
}
