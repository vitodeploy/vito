# Automation & Scheduled Tasks

## Introduction

Beyond the [Workflows](./workflows.md) you build yourself, Vito runs a number of background tasks on a schedule to keep your servers and instance healthy. These run automatically — you don't need to configure them — but it helps to know what's happening behind the scenes.

These tasks are driven by Vito's own scheduler, which runs on your Vito instance (not on your managed servers).

## What Vito Runs Automatically

| Task | Frequency | What it does |
| --- | --- | --- |
| Database backups | Hourly / daily / weekly / monthly | Runs the [backups](./servers/backups.md) that are due for their interval. |
| Metrics collection | Every minute | Collects [monitoring](./servers/monitoring.md) metrics from servers using Remote Monitor. |
| Metrics cleanup | Daily | Deletes metrics older than each server's retention window. |
| Server connection check | Every 5 minutes | Verifies Vito can still reach each server, and notifies you on disconnect/reconnect. |
| Server update check | Daily | Checks each server for available OS package updates. |
| Automatic server updates | Every minute | Applies OS package updates for servers that have [automatic updates](./servers/security.md#automatic-updates) enabled and are due. |
| Pending domain check | Every 5 minutes | Re-checks DNS resolution for domains that are not yet active. |
| Wildcard SSL renewal | Daily | Renews wildcard Let's Encrypt [certificates](./servers/ssl.md) within 30 days of expiry. |
| SSL expiry check | Daily | Flags certificates that are expiring soon and notifies you. |
| GitHub App sync | Every 4 hours | Syncs [GitHub App](./admin/github-app.md) installations as a fallback to webhooks. |
| Network reconciliation | Every 3 minutes | Re-applies [network](./networks/overview.md) configuration to servers that are pending or failed, and checks peer connectivity. |
| Database maintenance | Daily | Vacuums Vito's own database to keep it fast. |

:::info
[Provider networks](./networks/provider-networks.md) are **not** on this list. Vito only queries your cloud provider for private networks when you click **Sync**, so it never calls provider APIs in the background.
:::

## Per-Server Automatic Updates

Automatic OS package updates are opt-in per server. Enable them from the server's [Security](./servers/security.md#automatic-updates) page and set the schedule. You'll get a notification when an update completes.

## Notifications

Many automated tasks send [notifications](./settings/notification-channels.md) when something needs your attention — for example a failed backup, a disconnected server, an expiring SSL certificate, or a failed deployment.

:::info
For the scheduler to run, the Vito instance's worker/scheduler process must be running. This is set up
automatically by the Docker image and the VPS installer.
:::
