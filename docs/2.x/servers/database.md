# Database

## Introduction

Vito provides a simple interface to manage your databases disregards to their type, and you can create databases, users,
and
link users to databases.

## Supported Databases

- Mysql 5.7
- Mysql 8.0
- Mariadb 10.3
- Mariadb 10.4
- Mariadb 10.6
- Mariadb 10.11
- Mariadb 11.4
- Postgresql 12
- Postgresql 13
- Postgresql 14
- Postgresql 15
- Postgresql 16

## Install Database

To install a database, you can select the database type and version during the server creation.

Vito also allows you to install databases after the server creation in the [Services](./services) section.

:::info
Every server can have only one database type installed. For example, you can not install both Mysql and Postgresql on
the same server.
:::

## Uninstall Database

To uninstall a database, you can go to the [Services](./services) section and `Uninstall` the database you want to
remove.

## Linking

Creating a database and a user is not enough to use the database, You need to link the user to the database.

Vito provides a simple interface to link users to databases. You can simply link a user to multiple databases in the
`Database Users` section.

## Backup

Vito supports database auto backups. It runs backups for your databases and stores them in the external storages that
you provide.

To create a backup you need to connect a [storage provider](../settings/storage-providers) and then create the backup in the `Backups` section in the
`Databases` menu.

After creating a backup, you can restore the backup files into the same or other databases.
