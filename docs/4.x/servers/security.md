# Security

## Introduction

Each server has a **Security** section that acts as a hardening dashboard. From here you can review your server's security posture, harden SSH, manage the firewall and Fail2ban, and enable automatic package updates.

Vito calculates an overall **security score** based on the controls below, and you can run an on-demand check to refresh the current state.

## Security Score & Check

The Security page shows a score summarizing how hardened the server is. Click **Check** to re-run the on-demand security check, which inspects the server's current SSH configuration and installed security services and updates the displayed state.

## SSH Hardening

You can toggle two SSH controls from the Security page:

- **Password Authentication**: disable SSH password authentication so the server only accepts key-based logins. This is strongly recommended.
- **Root Login**: disable direct root SSH login. This control is only available when Vito connects as a non-root user.

:::warning
Make sure key-based access works before disabling password authentication, otherwise you could lock yourself out.
:::

## Firewall

If the server has no firewall yet, you can install [UFW](./firewall) directly from the Security page. Once installed, manage individual rules from the [Firewall](./firewall) section.

## Fail2ban

[Fail2ban](https://github.com/fail2ban/fail2ban) scans logs and bans IP addresses that show malicious signs, such as too many failed login attempts.

You can install and configure Fail2ban from the Security page with the following options:

- **maxretry**: number of failures before an IP is banned (default `5`).
- **findtime**: the window in which failures are counted, e.g. `10m`, `1h` (default `10m`).
- **bantime**: how long an IP stays banned, e.g. `10m`, `1h`, or `-1` for a permanent ban (default `10m`).
- **ignoreip**: a list of IP addresses or CIDR ranges to never ban (such as your own IP).

You can update these values later, or uninstall Fail2ban entirely, from the same page.

## Automatic Updates

You can enable automatic OS package updates on a schedule. When enabled, Vito runs unattended package updates on the server according to the schedule you set (a cron expression). You'll receive a notification when an automatic update completes.

:::info
Automatic updates apply OS package updates only. For upgrading installed services to new major versions, use the [Services](./services) page.
:::
