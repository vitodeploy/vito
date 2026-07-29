# Peers

## Introduction

**Peers** are devices that join a network without being a server Vito manages — a laptop, a CI runner, or an office router. A peer gets an address inside the network and can reach its servers privately.

Peers are only available on **WireGuard** networks. On custom and provider networks the tab is disabled, since Vito doesn't control the tunnel.

## Adding a Peer

Click **Add peer** and give it a name. Vito allocates the next free address from the network's block and adds the peer to every member's configuration.

You have two options for keys:

- **Let Vito generate the keys.** The simplest option. Vito creates the key pair and can show you a ready-to-use configuration file.
- **Bring your own public key.** Provide a public key you generated yourself. Vito never sees the private key, which means the device's key material never leaves it.

## Getting the Configuration

Use **Show config** to display the WireGuard configuration for a peer. Drop it into the WireGuard client on the device.

For peers where you supplied your own public key, Vito cannot know the private key, so the configuration contains `REPLACE_WITH_YOUR_PRIVATE_KEY` on the `PrivateKey` line. Substitute the key that stays on the device.

:::warning
A peer's configuration contains the key material that grants access to your private network. Treat it like a password.
:::

## Connecting from macOS

Here's the full path from adding a peer to reaching your servers from a Mac.

### 1. Install the WireGuard App

Install **WireGuard** from the [Mac App Store](https://apps.apple.com/us/app/wireguard/id1451685025). It's the official client and is free.

If you prefer the command line, `brew install wireguard-tools` gives you `wg` and `wg-quick` instead. The steps below use the app.

### 2. Add the Peer in Vito

On the network's **Peers** tab, click **Add peer** and give it a name such as `macbook`. Let Vito generate the keys — this is the simplest route and means the configuration you get is complete and ready to use.

### 3. Copy the Configuration

Use **Show config** on the peer you just created. You'll get a WireGuard configuration that looks roughly like this:

```ini
[Interface]
Address = 100.64.0.5/32
PrivateKey = <your peer's private key>

[Peer]
PublicKey = <the first server's public key>
AllowedIPs = 100.64.0.0/24
Endpoint = 203.0.113.10:51820
PersistentKeepalive = 25

[Peer]
PublicKey = <another server's public key>
AllowedIPs = 100.64.0.3/32
Endpoint = 203.0.113.11:51820
PersistentKeepalive = 25
```

There is one `[Peer]` block per server in the network. The first one carries the whole network range in `AllowedIPs` so traffic for the network is routed through the tunnel; the rest are scoped to their own address.

### 4. Create the Tunnel

In the WireGuard app, click the **+** button in the bottom-left and choose **Add empty tunnel…**, then paste the configuration over the placeholder contents. Give the tunnel a name and click **Save**.

macOS will ask for permission to add VPN configurations the first time — approve it.

:::info
You can also save the configuration as a file ending in `.conf` and use **Import tunnel(s) from file…** instead. The file name becomes the tunnel name.
:::

### 5. Activate and Test

Select the tunnel and click **Activate**. The status changes to *Active* once the handshake succeeds.

To confirm it's working, ping a server on its network address — you'll find it on the network's [Servers](servers.md) tab:

```shell
ping 100.64.0.2
```

Back in Vito, the peer's row on the **Peers** tab shows its last handshake once it has connected.

### Bringing Your Own Key

If you'd rather your private key never leave your Mac, generate a key pair first:

```shell
wg genkey | tee privatekey | wg pubkey > publickey
```

Give Vito the contents of `publickey` when adding the peer, then paste the contents of `privatekey` into the `PrivateKey` line of the configuration Vito shows you.

### Troubleshooting

- **The tunnel activates but nothing is reachable.** Check that the servers you want to reach are `active` on the network's [Servers](servers.md) tab, and that the network's [firewall rules](firewall.md) allow the port you're using.
- **No handshake at all.** The peer needs to reach the server's `Endpoint` address and UDP port from wherever you are. Some restrictive networks block outbound UDP.
- **It worked and then stopped.** If the peer's keys were regenerated in Vito, the old configuration is dead — copy the new one. The same applies if the network's listen port moved: adding a server that already uses the port for another network shifts this one to the next free port, and the `Endpoint` line in an already-imported configuration still points at the old one. Vito warns when this happens; download the configuration again.

For anything deeper — split tunnelling, routing all traffic through the network, or running WireGuard from the command line — see the [official WireGuard documentation](https://www.wireguard.com/quickstart/).

## Regenerating Keys

**Regenerate keys** issues a new key pair for a peer. The old configuration stops working immediately, so the device must be reconfigured with the new one. Use this if a device is lost or its key may have been exposed.

## Disabling a Peer

**Disable** removes the peer from every member's configuration without deleting it. Its address stays reserved, so enabling it again restores access on the same address.

This is a quick way to cut off a device temporarily without having to reissue its configuration afterwards.

## Removing a Peer

**Remove** deletes the peer and takes it out of every member's configuration. Its address is released back into the pool.

## Connection Status

Vito periodically asks one of the network's members for its last handshake with every peer, so the peers list shows whether a device is currently connected and when it was last seen. A peer keeps its tunnel alive with every member, so any one of them can answer for it.
