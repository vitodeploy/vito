<?php

namespace App\Notifications;

use App\Models\BackupFile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class FailedToDeleteBackupFileFromProvider extends AbstractNotification
{
    use Queueable;

    public function __construct(protected BackupFile $backupFile) {}

    public function rawText(): string
    {
        return "Failed to delete backup file: {$this->backupFile->name} on storage: {$this->backupFile->backup->storage->provider}";
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Failed to delete backup file from provider'))
            ->line($this->rawText())
            ->line('Please check your provider and delete it manually');
    }
}
