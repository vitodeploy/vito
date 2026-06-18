# Services

## Introduction

Vito gives you the ability to manage some of the installed services on your server. All the managed services are
`Systemd` services. Here you can run some of the `Systemd` commands on the supported services.

## Supported Services

- Nginx
- [PHP](./php)
- Mysql
- PostgreSQL
- Redis
- Supervisor
- UFW
- [Monitoring](./monitoring)
- Node.js

:::info
To add more services, you can develop a plugin for Vito. You can find more information about plugins in the [Plugins](../plugins.md#register-services) section.
:::

## Supported Operations

- Start
- Stop
- Restart
- Enable
- Disable
- Uninstall

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
