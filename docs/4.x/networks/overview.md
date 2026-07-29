# Networks

## Introduction

**Networks** let your servers talk to each other over private addresses instead of the public internet. A network belongs to a project, and every server in it can reach the others on an address that is never exposed publicly.

This is useful when you want a web server to reach a database server, or a group of application servers to share a cache, without opening those services to the world.

Networks live at the top of the sidebar, above **Servers**.

## Network Types

Vito supports three kinds of network. The type is chosen when the network is created and cannot be changed afterwards.

| Type | Membership | Addresses | Best for |
| --- | --- | --- | --- |
| **WireGuard** | You choose the servers | Allocated by Vito | Servers spread across different providers or regions |
| **Custom** | You choose the servers and their IPs | You pick an existing private IP per server | A private network you set up yourself |
| **Provider** | Synced from your cloud provider | Reported by the provider | Mirroring a VPC you already run at a cloud provider |

### WireGuard

Vito builds an encrypted [WireGuard](https://www.wireguard.com/) tunnel between the servers you select. It installs WireGuard, generates a key pair per server, allocates an address block, and writes the configuration to each machine.

Because the tunnel is encrypted and routed over the public internet, a WireGuard network works even when the servers are at different providers, in different regions, or behind NAT. It is the only type that supports [Peers](peers.md).

See [Creating a Network](create.md) to set one up.

### Custom

A custom network describes private connectivity that already exists — for example two servers at the same provider that can already reach each other on a private interface. Vito does not configure anything on the machines; you tell it which servers are in the network and which of their private IP addresses to use.

Vito still manages the [firewall rules](firewall.md) for the network, which is the main reason to define one.

### Provider

A provider network mirrors a private network (a VPC) that exists at your cloud provider. Vito discovers it by querying the provider's API, and keeps the membership in step with reality.

Provider networks are **read-only**: you cannot add, edit, or remove servers by hand, and you cannot delete the network while its provider connection still exists. See [Provider Networks](provider-networks.md).

## Statuses

A network shows one of the following statuses, derived from the state of its servers:

| Status | Meaning |
| --- | --- |
| `creating` | The network has no servers. A network created with servers moves straight to `syncing`. |
| `syncing` | At least one server is still being configured. |
| `active` | Every server is configured and in sync. |
| `failed` | At least one server failed to configure. Vito retries automatically. |
| `deleting` | The network is being torn down. |

Each server in the network carries its own status — `pending`, `updating`, `active`, `failed`, or `leaving` — which you can see on the [Servers](servers.md) tab.

:::info
Vito re-checks networks every three minutes and retries any server that is pending or failed, so a server that was offline when a change was made will pick it up once it is reachable again. See [Automation & Scheduled Tasks](../automation.md).
:::

## Network Pages

Once you open a network you'll find these tabs:

| Tab | What's in it |
| --- | --- |
| **Overview** | Status, address range, and counts for servers, peers, and firewall rules |
| **Servers** | The servers in the network and their addresses |
| **Peers** | Devices such as laptops or CI runners (WireGuard only) |
| **Firewall** | Rules controlling what traffic is allowed inside the network |
| **Logs** | Activity recorded against the network and the servers it runs on |
| **Settings** | Rename the network, review its details, and delete it |

The **Peers** tab is only available on WireGuard networks. On custom and provider networks the peer count on the Overview shows `N/A`.
