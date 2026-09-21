# Server Providers

## Introduction

Vito provides integration with multiple server providers to deploy your servers with ease.

When creating new servers, You can select the provider you've connected and deploy your servers with a few clicks.

A connected provider is also used to discover the private networks your servers already belong to — see [Provider Networks](../networks/provider-networks.md).

## Supported Providers

- AWS
- AWS Lightsail
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

### AWS Lightsail

Connect **AWS Lightsail** with an IAM access key ID and secret access key. This is a separate connection from the AWS (EC2) provider.

The IAM identity needs these permissions in the regions you use:

- `lightsail:GetRegions`
- `lightsail:GetBundles`
- `lightsail:GetBlueprints`
- `lightsail:ImportKeyPair`
- `lightsail:CreateInstances`
- `lightsail:GetInstance`
- `lightsail:PutInstancePublicPorts`
- `lightsail:DeleteInstance`
- `lightsail:DeleteKeyPair`

Allow `GetRegions` in `us-east-1` as well, because Vito uses it to verify the connection and list regions. See the [AWS Lightsail permissions reference](https://docs.aws.amazon.com/service-authorization/latest/reference/list_lightsail.html) for resource-level restrictions.

When creating a server, select the connected profile, region, plan, and Ubuntu version. Vito retrieves active Linux plans with public IPv4 addresses and selects an available Ubuntu image and availability zone. If AWS no longer offers the selected Ubuntu version, creation returns an error before allocating resources.

Vito creates an RSA SSH key for each instance and connects initially as `ubuntu`. The Lightsail firewall allows inbound traffic so that you can manage access through Vito's server firewall; include the firewall service when provisioning. Deleting a server with **Delete from provider** selected also removes its Lightsail instance and imported SSH key. Leaving that option off keeps both resources in AWS.

The instance uses its assigned public IPv4 address. Lightsail can change this address after a stop/start; automatic static IP allocation and provider private-network discovery are not included.

For the existing API, use `provider: "lightsail"` and send `key` and `secret` as top-level request fields when connecting a provider.

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

- The server must be running a fresh installation of Ubuntu 20.04, 22.04, or 24.04 x64.
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
