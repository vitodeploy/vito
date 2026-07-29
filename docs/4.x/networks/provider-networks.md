# Provider Networks

## Introduction

If your servers already sit inside a private network at your cloud provider — a VPC — Vito can discover it and mirror it as a **provider network**. You get the same [firewall management](firewall.md) and visibility as any other network, without describing the topology by hand.

Provider networks are created, updated, and removed entirely by syncing. They are read-only in the dashboard.

## Supported Providers

Vito reads private networks from the following [server provider](../settings/server-providers.md) connections:

| Provider | Product |
| --- | --- |
| AWS | VPC |
| DigitalOcean | VPC |
| Hetzner | Cloud Networks |
| Linode | VPC |
| Vultr | VPC |

Custom servers — those you added by IP address rather than through a provider connection — have no API to query and are skipped.

:::info
Vito only reads. It never creates, changes, or deletes a network at your provider.
:::

## Syncing

### Syncing Every Network

Click **Sync** on the Networks page. Vito looks at every server in the current project, groups them by the provider connection that created them, and asks each provider which private networks those servers belong to.

For every network it finds, Vito will:

- **Create** a provider network if it isn't in Vito yet.
- **Add or remove servers** so membership matches the provider.
- **Update addresses** when a server's private IP has changed.
- **Delete** the network if it no longer exists at the provider.

Syncing runs in the background. The networks list updates as it progresses.

### Syncing One Network

Open a provider network and click **Sync** on its **Servers** tab to reconcile just that network. If the network no longer exists at the provider, it is removed from Vito.

### Sync Is Manual

Vito does not sync provider networks on a schedule — it only happens when you click **Sync**. Changes you make directly in your provider's console will not appear in Vito until you do.

## What Gets Imported

Vito is deliberately conservative about what it pulls in.

- **Only networks containing your servers.** A VPC is imported only if at least one of its instances is a server Vito manages in the current project. Unrelated VPCs in your cloud account are ignored.
- **Only servers Vito manages.** Instances that Vito doesn't know about are ignored — they get no entry on the Servers tab and are not covered by the network's firewall rules.
- **Only the current project.** Syncing affects the project you're viewing, using the provider connections its servers were created with.

:::warning
Because instances Vito doesn't manage are ignored, a provider network's firewall rules only cover the members you can see. Other machines in the same VPC are unaffected by them.
:::

## What You Can and Can't Change

Membership belongs to the provider, so Vito won't let you edit it:

| Action | Provider network |
| --- | --- |
| Add a server | Not available — attach the instance at your provider, then sync |
| Change a server's IP | Not available — the provider reports it |
| Remove a server | Not available — detach the instance at your provider, then sync |
| Manage firewall rules | **Available** |
| Rename the network | **Available** |
| Delete the network | Not available while Vito can still sync it |

Renaming is safe: Vito sets the name once, when the network is first imported, and never overwrites it afterwards. If you rename the VPC at your provider, your name in Vito stays.

## Deleting

You cannot delete a provider network while the connection it came from still exists — it would simply be re-imported on the next sync. Delete the network at your provider and sync instead.

The exception is a network Vito can no longer sync, which becomes deletable so it isn't stranded. Its Settings page shows a notice explaining which case applies:

- **Orphaned** — you deleted the [server provider connection](../settings/server-providers.md) it came from.
- **Cannot be synced** — none of its servers still carries the identifier the provider knows them by, so Vito has no way to ask the provider about the network. This is rare; it usually means a server's provider details were changed outside Vito.

## Addresses and Firewall Rules

Provider networks use the private addresses reported by the provider. Their [firewall rules](firewall.md) are scoped to each member's individual address rather than the whole VPC range, so a rule only ever opens a port to the specific servers Vito manages.

## Troubleshooting

**Nothing was imported.** Check that your servers were created through a provider connection, and that at least one of them is attached to a network at the provider.

**A network didn't sync.** If Vito can't reach a provider — an invalid token, a permissions error, or a rate limit — it skips that connection and leaves its networks untouched rather than risk deleting them. Try again, and check the connection under [Server Providers](../settings/server-providers.md).

**Permission errors.** Reading private networks needs a token with permission to list networks and instances. Some providers scope this separately from the permissions Vito needed when the connection was first made — for example DigitalOcean requires the `vpc:read` scope. A connection that works for creating servers may still need its token updated.

**A duplicate-looking network appeared.** If a discovered network's name clashes with an existing one in the project, Vito appends a suffix (for example `default-2`) rather than failing.
