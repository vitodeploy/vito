# Server Overview

## Introduction

The Overview page is the landing page for a server. It gives you a quick health check at a glance:
any alerts that need attention, basic monitoring stats, recent activity, and the common server
actions.

## Monitoring stats

At the top of the page Vito shows summary cards for the server's core resources:

- **CPU Load**
- **Memory Usage**
- **Disk Usage**

Each card reflects the latest collected metric and links through to the
[Monitoring](/docs/4.x/servers/monitoring) page, where you can see the full history and more detailed
charts.

:::info
Monitoring stats are collected by the Vito agent on the server. If the agent is not installed or is
out of date, some stats may be empty.
:::

## Recent logs

Below the stats, the Overview lists the server's most recent logs (provisioning steps, service
actions, and other operations Vito has run). The full history is available on the
[Logs](/docs/4.x/servers/logs) page.

## Server actions

The actions menu in the top right of the server pages gives you quick access to common operations:

- **Check connection** - re-checks that Vito can reach the server over SSH and updates its status.
- **Restart** - reboots the server.
- **Check for updates** - asks the server how many OS package updates are pending.
- **Update** - installs the pending OS package updates. This is only available when updates are
  available.

## Server alerts

Vito surfaces **server alerts** to flag conditions that need your attention. They appear at the top
of the server's pages and on the servers listing, and each includes a call to action so you can
resolve it quickly. Alerts update in real time and clear automatically once the underlying condition
is resolved.

The following alerts can appear:

### Restart required

Shown when the kernel or a critical package has been updated and the server needs a reboot to
complete the upgrade. Provides a **Restart** action.

### Package updates available

Shown when the server has pending OS package updates. It includes the number of updates and provides
an **Update** action to install them.
