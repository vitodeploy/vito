# Network Firewall

## Introduction

A network's **Firewall** tab controls what traffic is allowed between its members. Vito turns each rule into real firewall rules on every server in the network, using the server's own [UFW firewall](../servers/firewall.md).

This is the main reason to define a network even when the private connectivity already exists: you describe the rule once, for the network, and Vito keeps it applied on every member.

## The Default Rule

Every new network starts with a single **Allow all** rule, which lets members reach each other on any port and protocol. Delete it and add narrower rules when you want to lock things down.

## Creating a Rule

Click **Create** and provide:

### Name

A label for the rule, for example `mysql` or `redis`.

### Protocol

The protocol to allow — TCP or UDP. Leave it empty to allow any protocol.

### Port

The port to allow, for example `3306`, or a range such as `3000:3010`. Leave it empty to allow any port.

Protocol and port are independent — all four combinations are valid:

| Protocol | Port | Allows |
| --- | --- | --- |
| TCP | `3306` | TCP on port 3306 |
| empty | `3306` | any protocol on port 3306 |
| UDP | empty | UDP on any port |
| empty | empty | all traffic from the network |

:::info
A port **range** must name a protocol — UFW rejects a multi-port rule that doesn't. A single port works with or without one.
:::

## How Rules Are Applied

Vito translates each network rule into a rule on every member's firewall, scoped to the other members of that network:

- On **WireGuard** networks, and on **custom** networks that have a CIDR, rules are scoped to the network's address range.
- On **custom** networks without a CIDR, and on all **provider** networks, rules are scoped to each member's individual address instead. This is tighter — the port is opened only to the specific servers in the network, not to everything sharing the range.

WireGuard networks also get an automatic rule opening the tunnel's listen port to the other members, so the tunnel can be established. This rule is managed by Vito and is not affected when you delete the **Allow all** rule.

Adding a [peer](peers.md) widens that rule to any source, because a laptop or CI runner connects from an address Vito can't know in advance. Only the tunnel's UDP listen port is opened; traffic still has to authenticate with a known peer key before it reaches anything. Remove every peer and the rule narrows back to the other members.

:::info
Network rules appear on each server's own [Firewall](../servers/firewall.md) page as managed rules. They're shown there for visibility but are edited from the network, so they stay consistent across every member.
:::

## Servers Without a Firewall

A server with no firewall service installed still shows in the network, but nothing is enforced on it — the Servers tab shows whether each member has a firewall.

If you install UFW on a server later, Vito applies the network's rules as part of the installation.

## Deleting a Rule

Deleting a rule removes it from every member's firewall.

:::warning
Deleting the **Allow all** rule locks the network down to only the ports you've explicitly allowed. Make sure the services your servers rely on — a database port, for example — have their own rules first.
:::
