# Firewall

## Introduction

It is essential to a server to have a firewall to protect it from unauthorized access. Vito provides a simple, yet
powerful interface to manage your server's firewall.

Vito installs [UFW](https://help.ubuntu.com/community/UFW) as a firewall service into your server.

## Rules

By default, Vito allows only the following ports after the server creation:

- 22 (SSH) - This is required to connect to your server
- 80 (HTTP)
- 443 (HTTPS)

:::warning
These rules are applied in the server level, Your server provider might have additional rules or restrictions and make sure your server provider's rules are compatible with Vito's rules.
:::

:::danger
Do not remove the SSH rule, This will cause Vito to lose connection to your server.
:::

## Create new Rule

You can easily create firewall rules and deploy them to your server.

Here are more info about the fields you need to provide to create a new rule:

### Rule Type

Specify the rule type which can be `Allow` or `Deny`

### Protocol

Select the protocol you want to apply the rule to

### Port

Incoming port which you are going to apply the rule to

### Source

The Source is the IP address which you want to apply the rule to.

### Mask

If the Source is a range then you can specify the range here.

For example if the range is `10.0.0.0/24` then the Source will be `10.0.0.0` and the Mask will be `24`.