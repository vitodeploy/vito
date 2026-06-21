<?php

namespace App\Notifications;

use App\Models\BackupFile;
use Illuminate\Notifications\Messages\MailMessage;

class FailedToDeleteBackupFileFromProvider extends AbstractNotification
{
    public function __construct(protected BackupFile $backupFile) {}

    public function rawText(): string
    {
        return "Failed to delete backup file: {$this->backupFile->name} on storage: {$this->backupFile->backup->storage->provider}";
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Failed to delete backup file'))
            ->line(__('We couldn\'t delete the backup file [:file] from :provider.', [
                'file' => $this->backupFile->name,
                'provider' => $this->backupFile->backup->storage->provider,
            ]))
            ->line(__('Please check your storage provider and remove it manually.'));
    }
}
