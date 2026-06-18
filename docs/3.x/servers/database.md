# Database

## Introduction

Vito provides a simple interface to manage your databases disregards to their type, and you can create databases, users,
and
link users to databases.

## Supported databases

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
- Postgresql 17

## Install database service

To install a database, you can select the database type and version during the server creation.

Vito also allows you to install databases after the server creation in the [Services](./services.md#install) section.

:::info
Every server can have only one database type installed. For example, you can not install both Mysql and Postgresql on
the same server.
:::

## Uninstall database

To uninstall a database, you can go to the [Services](./services.md#uninstall) section and `Uninstall` the database you
want to
remove.

:::warning
Vito won't allow the database service to be uninstalled if there are any databases or users or backups created for that.
You will need to delete them first.
:::

## Create database

To create a database, you can go to the `Databases` section in the server's menu and click on the `Create` button.

Enter a unique name for the database and select a charset and collation for the database.

The charsets and collations are fetched from the database server when the database service is installed.

## Delete database

To delete a database, you can go to the `Databases` section in the server's menu and click on the `Delete` in the
database menu. Vito will ask you to confirm the deletion of the database.

## Sync databases

Sometimes you might need to create a database manually on the server, or you might have created a database using the
command line.

Vito allows you to sync the databases that are created manually on the server with the Vito's database management
system.

To sync the databases, you can go to the `Databases` section in the server's menu and click on the `Sync` button.

:::info
Syncing databases will also sync the charsets and collations of the databases again.
:::

## Create database user

To create a database user, you can go to the `Database Users` section in the server's menu and click on the `Create`

You can allow a database user to be accessed from outside the server by checking the `Allow remote access`. Then you
will need to provide the IP address or hostname of the remote machine that will access the database.

Users can have the following permissions:

**MySQL/MariaDB:**

- READ: SELECT, SHOW VIEW
- WRITE: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, LOCK TABLES, REFERENCES, SHOW VIEW, TRIGGER, CREATE VIEW, EXECUTE (excludes DROP and TRUNCATE)
- ADMIN: ALL PRIVILEGES

**PostgreSQL:**

- READ: CONNECT, USAGE, SELECT on database, schemas, tables, and sequences
- WRITE: CONNECT, USAGE, CREATE, SELECT, INSERT, UPDATE, DELETE, REFERENCES, TRIGGER, EXECUTE (excludes DROP and TRUNCATE)
- ADMIN: ALL PRIVILEGES on database, tables, and sequences

## Link user to database

Creating a database and a user is not enough to use the database, You need to link the user to the database.

Vito provides a simple interface to link users to databases. You can simply link a user to multiple databases in the
`Database Users` section.

## Sync database users

Sometimes you might need to create a database user manually on the server, or you might have created a user using the
command line.

Vito allows you to sync the database users that are created manually on the server with the Vito's database management
system.

## Backup

Backups are explained [Here](./backups.md#database-backups)
