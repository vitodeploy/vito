# Database Backups

VitoDeploy supports database auto backups. It runs backups for your databases and stores them in the external storages that you provide.

Currently VitoDeploy supports the following Storage Providers:

- Dropbox
- SFTP (Beta)

## Create Backup

To create backups, You need to navigate to `Database` menu and create a new Backup.

It will ask you to select the Database and the Storage that you already connected ([How](../settings/storage-providers)) and the Interval of the backups and how many backup files to keep when making a new backup.

## Restore Backup

After creating a backup you can navigate to its Files and there you will be able to restore the backup files to the same or different databases.