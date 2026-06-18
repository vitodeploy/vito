# Services

## Introduction

Vito gives you the ability to manage some of the installed services on your server. All the managed services are
`Systemd` services. Here you can run some of the `Systemd` commands on the supported services.

## Supported Services

- Nginx
- Caddy (beta)
- [PHP](./php)
- MySQL
- MariaDB
- PostgreSQL
- Redis
- Valkey
- Supervisor
- UFW
- Fail2ban
- [Monitoring](./monitoring) (VitoAgent / RemoteMonitor)
- GoAccess

:::info
To add more services, you can develop a plugin for Vito. You can find more information about plugins in the [Plugins](../plugins.md#register-services) section.
:::

## Supported Operations

- Start
- Stop
- Restart
- Reload (reloads the service's configuration without a full restart, where supported)
- Enable
- Disable
- Uninstall

## Edit Service Configuration

For many services, Vito lets you edit the main configuration file directly from the dashboard. Open the service's `Config` action to view and edit files such as:

- Nginx — `/etc/nginx/nginx.conf`
- MySQL / MariaDB — `/etc/mysql/my.cnf`
- PostgreSQL — `/etc/postgresql/{version}/main/postgresql.conf`
- Redis — `/etc/redis/redis.conf`
- Valkey — `/etc/valkey/valkey.conf`
- Supervisor — `/etc/supervisor/supervisord.conf`

After saving, reload or restart the service for the changes to take effect.

:::warning
Editing configuration files incorrectly can break a service. Make sure you understand the change before saving.
:::

## Service Logs

Services that expose logs (for example Nginx, MySQL, PostgreSQL, Redis) have their logs available under the [Logs](./logs#service-logs) section of the server.

## Install

You can install new services from the [Supported Services](#supported-services) section.

## Uninstall

Vito enables you to uninstall services that you don't want.

For example, you want to change your database from Mysql to PostgreSQL. You will need to uninstall Mysql first and then
install PostgreSQL.

:::warning
You cannot uninstall a service that is being used by other resources. For example, you cannot uninstall Nginx if you
already have sites running on the server.
:::
