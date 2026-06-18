# DNS Providers

## Introduction

Vito provides integration with DNS providers to to manage your domains' NS records.

When [adding a new domain](../domains), You need to select the DNS provider you've connected so Vito can list your domains and manage their NS records.

## Supported Providers

- Cloudflare
- Custom (Bring your own provider)

## Required Permissions

Here you can see the required permissions for each provider's API Keys.

### Cloudflare

- Zone:Zone:Read, Zone:DNS:Edit

### Custom

If your DNS provider is not listed here, You can develop a plugin to integrate your DNS provider with Vito. Please refer to the [plugin development documentation](../plugins) for more information.

## Scope

DNS providers can be created under a specific project or globally.

If you create a server provider under a project, it will only be available for that project.

If you create a server provider globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which server provider they can
access.

:::info
In any scope, only you will have access to see or use that provider and other users of the project will not be able to see or use it.
:::
