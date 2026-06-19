<?php

namespace App\Notifications;

use App\Models\BackupFile;
use App\Models\Server;
use Illuminate\Notifications\Messages\MailMessage;

class RestoreFailed extends AbstractNotification
{
    public function __construct(protected Server $server, protected BackupFile $backupFile) {}

    public function rawText(): string
    {
        return __("Restoring the backup [:name] on server [:server] failed.\nCheck the backup logs for details.\n:link", [
            'name' => $this->backupFile->name,
            'server' => $this->server->name,
            'link' => url('/servers/'.$this->server->id.'/backups/'.$this->backupFile->backup_id),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Backup restore failed'))
            ->line(__('Restoring the backup [:name] on your server [:server] failed to complete.', [
                'name' => $this->backupFile->name,
                'server' => $this->server->name,
            ]))
            ->line(__('Check the backup logs to find out what went wrong.'))
            ->action(__('View backup'), url('/servers/'.$this->server->id.'/backups/'.$this->backupFile->backup_id));
    }
}
