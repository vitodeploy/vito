# Network Servers

## Introduction

The **Servers** tab lists the servers that belong to a network, the address each one uses inside it, and whether the server has a firewall installed.

## Adding a Server

Click **Add server** and choose a server from the project.

On a **WireGuard** network, Vito allocates the next free address from the network's block, generates a key pair, installs WireGuard if needed, and updates every other member so they know about the new one.

On a **Custom** network, you also choose which of the server's private IP addresses to use.

Adding a server is not available on [provider networks](provider-networks.md) — attach the instance at your cloud provider and sync instead.

:::info
A server can belong to several networks at once. On WireGuard networks, each one gets its own interface and its own listen port.
:::

## Server Status

Each member shows its own status:

| Status | Meaning |
| --- | --- |
| `pending` | Waiting to be configured — usually because the server is offline |
| `updating` | Vito is applying the configuration now |
| `active` | Configured and in sync |
| `failed` | Configuration failed; Vito retries automatically |
| `leaving` | Being removed from the network |

Vito re-checks pending and failed members every three minutes, so a server that was unreachable is picked up once it is back. That interval is how often a retry is attempted, not a deadline - a member that keeps failing is retried across several passes before it converges. After several consecutive failures the member drops to an hourly retry instead, so a server that stays broken for a long time no longer costs a sync attempt every three minutes, and still recovers on its own once it is fixed. **Sync** on the member forces an immediate attempt at any point.

## Changing a Server's IP

On a **Custom** network, use **Edit** to change which private IP address the server uses in this network. Vito re-applies the network's firewall rules with the new address.

This is not available on WireGuard networks, where Vito owns the addressing, or on provider networks, where the provider does.

## Regenerating Configuration

On a **WireGuard** network, **Regenerate** re-applies the network configuration to a single server. Use it if a server's tunnel has drifted or you want to force a rewrite.

## Syncing the Whole Network

**Sync** re-applies the configuration to every server in the network. On a [provider network](provider-networks.md) it does something different: it queries your cloud provider and reconciles membership.

## Removing a Server

**Remove** takes the server out of the network. Vito tears the configuration down on that machine, removes the network's firewall rules from it, and updates the remaining members so they stop routing to it.

If the server is unreachable — for example it has already been destroyed — Vito retries for a few minutes and then removes the membership anyway so the network can settle.

Removing a server is not available on provider networks.

:::warning
Removing a server drops its private connectivity immediately. Make sure nothing is relying on that address first.
:::
