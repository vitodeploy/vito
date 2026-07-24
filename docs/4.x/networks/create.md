# Creating a Network

## Introduction

Click **Create** on the Networks page to create a network. You can create **WireGuard** and **Custom** networks by hand.

**Provider** networks cannot be created here — they appear automatically when Vito discovers them at your cloud provider. See [Provider Networks](provider-networks.md).

## Creating a WireGuard Network

Choose **WireGuard** as the type and fill in the following fields.

### Name

A name for the network, unique within the project.

### Address Pool

The range Vito allocates the network's address block from:

- **CGNAT (100.64.0.0/10)** — the default. This range is reserved for carrier-grade NAT and is almost never used on a server's own interfaces, so it very rarely collides with anything.
- **Private (RFC1918)** — allocates from `10.0.0.0/8` or `192.168.0.0/16`.

:::info
The RFC1918 option deliberately avoids `172.16.0.0/12`, because Docker and several cloud providers use it by default. Vito also skips ranges already used by your servers and a blocklist of common provider ranges.
:::

### Block Size

The prefix length of the block to allocate, between `/16` and `/28`. The default `/24` gives you 253 usable addresses for servers and peers combined.

### Primary Server

The first server to join the network. You can add more from the [Servers](servers.md) tab once the network exists.

### Listen Port

The UDP port WireGuard listens on, `51820` by default. If a selected server is already in another WireGuard network using that port, Vito picks the next free one.

Vito opens this port in the server's firewall automatically, restricted to the other members of the network.

## Creating a Custom Network

Choose **Custom** as the type. Custom networks describe connectivity that already exists, so Vito does not configure anything on the servers.

### CIDR

Optional. The address range of the existing private network, for example `10.0.0.0/24`.

If you provide it, [firewall rules](firewall.md) are scoped to the whole range. If you leave it empty, rules are scoped to each member's individual address instead, which is tighter.

### Primary Server and Private IP

Select the first server, then choose which of its private IP addresses belongs to this network.

The list contains the private addresses Vito has discovered for that server. If the address you need is missing, click the refresh action to re-detect the server's addresses, or add it from the server's [Network](../servers/network.md) page.

:::warning
Each private IP address can belong to only one network member across your whole instance. If an address is already used by another network, pick a different one.
:::

## After Creation

Vito creates the network and starts configuring it:

- **WireGuard networks** — WireGuard is installed on each server if needed, keys are generated, and the tunnel is brought up. The network moves to `active` once every server is configured.
- **Custom networks** — members are marked active immediately, and the network's firewall rules are applied.

Every new network is seeded with a single **Allow all** firewall rule so members can reach each other on any port. Tighten this from the [Firewall](firewall.md) tab.

:::info
If a server is offline when you create the network, its member stays `pending` and Vito configures it automatically once the server is reachable again.
:::
