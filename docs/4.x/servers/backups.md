# Backups

## Introduction

Vito provides a simple interface so you can backup your server's files, directories or even databases.

## Storage Provider

In order to start backing up, you need to first configure a storage provider where the backups will be stored.

See [Storage Providers](../settings/storage-providers.md) documentation for more information.

## Database Backups

Navigate to the `Backups` section in the server's menu and click on the `Create Backup` button. Select `Database` as backup type and then select the database to backup and storage provider and how many backups you want vito to keep and then backup interval and start the backup.

Vito will backup the selected databases into the connected storage provider automatically when you choose one of the predefined intervals.

Database dumps are streamed directly into a compressed `.sql.gz` archive as they are created. Nothing uncompressed is written to disk first, so backups use minimal space on your server and upload faster. MySQL and MariaDB dumps are taken with a consistent, non-locking snapshot (`--single-transaction`), so backing up your transactional (InnoDB) tables does not interrupt your application. Non-transactional engines such as MyISAM are not covered by this snapshot and may briefly lock during the dump.

Vito's scheduled backup worker runs every minute and triggers each backup whose interval is due, so the predefined intervals (hourly, daily, weekly, monthly), `Every Minute`, and custom cron expressions are all run automatically. You can also trigger any backup on demand at any time with the **Run** action on the backup.

:::info
Older backups will be deleted automatically based on the number of backups you want to keep.
:::

### Faster compression with pigz

By default, Vito compresses database dumps with `gzip`, which is single-threaded. For large databases the compression step can become the slowest part of a backup.

If [`pigz`](https://zlib.net/pigz/) (a parallel, multi-core drop-in replacement for gzip) is installed on the server, Vito detects it automatically and uses it instead — compressing across all available CPU cores and significantly reducing backup time on large databases.

Install it on the server with:

```bash
sudo apt install pigz
```

:::tip
Installing `pigz` is completely optional. If it is not installed, Vito falls back to `gzip` automatically. The output format is identical either way (a standard `.sql.gz` archive), so backups created with or without `pigz` restore the same way — there is nothing else to configure.
:::

### Large databases

A single backup run has a time limit (20 minutes by default). Very large databases may need more time. You can raise the limit by setting `BACKUP_RUN_TIMEOUT` (in seconds) in your Vito instance's `.env` file:

```dotenv
BACKUP_RUN_TIMEOUT=7200
```

Installing `pigz` (see above) and the streamed compression also help large dumps finish well within the limit.

## Restore Database Backup

To restore a database backup, navigate to the `Backups` section and find the backup you want to restore and then navigate to its files by selecting the actions dropdown and then `Files`.

In the files section, select the backup file you want to restore and then click on the `Restore` button.

Select the target database and then click on the `Restore` button.

:::tip
You can restore a database backup to another database.
:::

:::warning
Database backups created in **v3.x are stored as `.zip` and cannot be restored in v4.x**. v4 stores database backups as compressed `.sql.gz` archives and can only restore backups created in v4. After upgrading, take a fresh database backup so you have a restorable v4 copy. See [Breaking Changes](../prologue/breaking-changes#database-backup-format) for details. (File/directory backups are unaffected.)
:::

## File/Directory Backups

Navigate to the `Backups` section in the server's menu and click on the `Create Backup` button. Select `File Backup` as backup type and then enter the path on your server that you want to backup and storage provider and how many backups you want vito to keep and then backup interval and start the backup.

Vito will compress the given path with `tar` and then upload it to the connected storage provider automatically when you choose one of the predefined intervals.

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

## Enabling & Disabling Backups

You can pause a scheduled backup without deleting it. Open the actions dropdown on a backup and choose **Disable** to stop it from running on its schedule, and **Enable** to resume it. A disabled backup is clearly marked in the backups list.

This is useful when you want to temporarily stop a schedule — for example during maintenance or a large migration — without losing the backup's configuration and history.

:::info
Disabling a backup only stops future scheduled runs. The backup files you have already created remain in your storage provider and can still be restored.
:::

## Backup Failures & Notifications

If a backup or restore fails, Vito records what went wrong and surfaces it instead of failing silently:

- The failed backup file is marked as failed in the backups list, and you can hover the error indicator next to its status to see the reason.
- A notification is sent to your configured [notification channels](../settings/notification-channels.md) when a backup fails, and when a restore fails.

A failed run does **not** disable the schedule — Vito will try again at the next interval. If a backup job is interrupted (for example the server is rebooted mid-run), Vito automatically reconciles any backup left in a stuck state and marks it as failed so it no longer appears to be in progress.
