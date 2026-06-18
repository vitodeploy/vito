# Server Providers

## Introduction

Vito provides integration with multiple server providers to deploy your servers with ease.

When creating new servers, You can select the provider you've connected and deploy your servers with a few clicks.

## Supported Providers

- AWS
- Akamai (Linode)
- Digital Ocean
- Vultr
- Hetzner
- Custom (Bring your own provider)

## Required Permissions

Here you can see the required permissions for each provider's API Keys.

### AWS

- AWS IAM users must have Programmatic API Access.
- AWS IAM users need to belong to a group with the `AmazonEC2FullAccess` managed policies.

### Linode

- `Linodes` (Read/Write)

### Digital Ocean

- `droplet`: create, read, update, delete
- `ssh_key`: create, read, update, delete

### Vultr

Personal Access Token allows full access on Vultr.

### Hetzner

Read/Write API Token

### Custom

If your server provider is not listed here, you can use the `Custom` provider when creating a new server.

Your server must have the following requirements so Vito can provision it:

- The server must be running a fresh installation of Ubuntu 22.04 or 24.04 x64.
- The server must be accessible externally over the Internet.
- The server must have root SSH access enabled.
- The server requirements should meet the following criteria or more: 1 CPU Core with 1GHz, 1GB RAM, and 10GB Disk space.
- The server must have curl installed.

## Scope

Server providers can be created under a specific project or globally.

If you create a server provider under a project, it will only be available for that project.

If you create a server provider globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which server provider they can
access.

:::info
In any scope, only you will have access to see or use that provider and other users of the project will not be able to see or use it.
:::
