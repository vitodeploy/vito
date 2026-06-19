# Backups

## Introduction

Vito provides a simple interface so you can backup your server's files, directories or even databases.

## Storage Provider

In order to start backing up, you need to first configure a storage provider where the backups will be stored.

See [Storage Providers](../settings/storage-providers.md) documentation for more information.

## Database Backups

Navigate to the `Backups` section in the server's menu and click on the `Create Backup` button. Select `Database` as backup type and then select the database to backup and storage provider and how many backups you want vito to keep and then backup interval and start the backup.

Vito will backup the selected databases into the connected storage provider on the given interval.

:::info
Older backups will be deleted automatically based on the number of backups you want to keep.
:::

## Restore Database Backup

To restore a database backup, navigate to the `Backups` section and find the backup you want to restore and then navigate to its files by selecting the actions dropdown and then `Files`.

In the files section, select the backup file you want to restore and then click on the `Restore` button.

Select the target database and then click on the `Restore` button.

:::tip
You can restore a database backup to another database.
:::

## File/Directory Backups

Navigate to the `Backups` section in the server's menu and click on the `Create Backup` button. Select `File Backup` as backup type and then enter the path on your server that you want to backup and storage provider and how many backups you want vito to keep and then backup interval and start the backup.

Vito will compress the given path with `tar` and then upload it to the connected storage provider on the given interval.

:::info
Older backups will be deleted automatically based on the number of backups you want to keep.
:::

## Restore File/Directory Backup

To restore a file/directory backup, navigate to the `Backups` section and find the backup you want to restore and then navigate to its files by selecting the actions dropdown and then `Files`.

In the files section, select the backup file you want to restore and then click on the `Restore` button.

Select the target path on your server and then click on the `Restore` button.

:::warning
Target path is the final path the file/directory will be restored to. For example if you have backed up `/home/vito/your-site.com`, and you want to restore it to `/home/vito/other-site.com`, you need to enter `/home/vito/other-site.com` as target path.

If there was `index.php` inside `your-site.com`, it will be now in `/home/vito/other-site.com/index.php`.
:::
