# Network

## Introduction

The **Network** section of a server lets you view and manage the server's IP addresses. Vito detects the addresses configured on the server and lets you add or remove additional ones.

:::info
This page is about the addresses on a single server. To connect several servers together privately, see [Networks](../networks/overview.md). A [custom network](../networks/create.md) is built from the private addresses listed here.
:::

## Viewing IP Addresses

The Network page lists every IP address Vito knows about for the server, including:

- The **address** itself.
- The **family** — IPv4 (`inet`) or IPv6 (`inet6`).
- The **type** — public, private, or unknown.
- Whether it is the **primary** address.
- Its **status** — `configuring`, `configured`, `deleting`, or `failed`.

## Refreshing

Click **Refresh** to re-detect the IP addresses configured on the server. This is useful after you add or remove an address outside of Vito.

## Adding an IP Address

You can add a secondary IP address to the server. Provide the IP address, the network interface, and the prefix length. Vito will configure the address on the server, and its status will move to `configured` once done.

## Setting the Primary IP

One address can be marked as the **primary** IP. Use the **Set as primary** action on an address to change which one is primary.

## Deleting an IP Address

You can remove a secondary IP address from the server. Vito unconfigures it on the server and removes it from the list.

:::warning
Removing an IP address that a site or service depends on can break connectivity. Make sure the address is not in use before deleting it.
:::
