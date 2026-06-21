# Domains

:::info
This page is about the domains attached to a specific **site** and their per-domain SSL. To manage a
domain's **DNS records** through a connected DNS provider, see [Domains](../domains.md).
:::

## Introduction

Every site in Vito is reachable through one or more **hosted domains**. The domain you enter when
creating a site becomes its **primary** domain, and you can attach additional domains to the same
site at any time, either as aliases or as redirects.

Each domain has its own DNS resolution status and its own SSL configuration, and Vito keeps the
webserver vhost in sync as you add, change, or remove domains.

## Domain types

| Type         | Description                                                                                  |
| ------------ | -------------------------------------------------------------------------------------------- |
| **Primary**  | The site's main domain, set when the site is created. It cannot be deleted or deactivated.   |
| **Alias**    | Serves the same site content under another domain (e.g. `www.example.com` shows `example.com`). |
| **Redirect** | Sends visitors to the primary domain via an HTTP redirect (e.g. `old.example.com` to `example.com`). |

## Adding a domain

From the site's **Domains** page, click **Add Domain** and fill in:

- **Domain** - the hostname to add, for example `sub.example.com`.
- **Type** - **Alias** or **Redirect** (see above).
- **SSL** - how the domain's certificate is handled (see [SSL per domain](#ssl-per-domain)).

A domain must be unique per server. Vito will reject a domain that is already in use by another
site on the same server.

## DNS validation

When you add a domain, Vito checks that it actually resolves to the server before activating it.

A domain that does not yet point at the server is held in a **pending** state and flagged on the
site, rather than being activated with a configuration that would not work. Vito automatically
re-checks pending domains **every 5 minutes for the first 24 hours**, activating them as soon as
they resolve.

You do not have to wait for the scheduled check. From the UI you can **validate the domain on
demand**: if it now resolves to the server, the domain activates immediately and the pending warning
clears.

### Domain status

| Status       | Meaning                                                       |
| ------------ | ------------------------------------------------------------- |
| **creating** | The domain is being set up on the server.                     |
| **updating** | A change to the domain is being applied.                      |
| **pending**  | The domain does not yet resolve to the server.                |
| **active**   | The domain resolves and is serving traffic.                   |
| **inactive** | The domain has been deactivated and is not served.            |
| **deleting** | The domain is being removed.                                  |

### Validation behind a proxy

If your server sits behind a proxy or load balancer, a plain DNS-to-IP check is not always enough.
For these cases Vito performs an HTTP/HTTPS challenge: it writes a short-lived token and serves it
from `/.well-known/vito` on the site, then fetches that path over each address the domain resolves
to and confirms the response matches.

:::info
The challenge is signed with your instance's `APP_KEY`, so another Vito installation cannot spoof
ownership of your domain.
:::

## SSL per domain

Each domain can have its own SSL method:

| Method                                 | Description                                                          |
| -------------------------------------- | ------------------------------------------------------------------- |
| **Disabled**                           | No SSL is configured for the domain.                                |
| **Generate Let's Encrypt Certificate** | Vito issues and manages a Let's Encrypt certificate for the domain. |
| **Custom Certificate**                 | Use an existing server-level certificate, including wildcards.      |

When you choose **Custom Certificate**, Vito lists only the server-level certificates that match the
domain you entered. Server-level and wildcard certificates are managed from the server's SSL
settings.

:::info
Wildcard Let's Encrypt certificates are renewed automatically when they are within 30 days of
expiry, provided the domain is still reachable through a configured DNS provider. Let's Encrypt
certificates issued per domain also auto-renew, and Vito keeps the recorded expiry date in sync.
:::

The available SSL methods depend on the webserver. Some webservers manage certificates differently,
so the options shown reflect what that webserver supports.

## Activating and deactivating domains

You can deactivate a non-primary domain to temporarily stop serving it without deleting it, and
reactivate it later. The primary domain cannot be deactivated.

## Deleting a domain

Non-primary domains can be deleted from the Domains page. A domain cannot be deleted while it is
still assigned to a server-level (for example, wildcard) SSL certificate. Remove the assignment
first, then delete the domain.

## VHost generation

Adding, changing, or removing domains regenerates the site's vhost from its template. In v4.x the
vhost is rendered with Mustache rather than Blade.

:::warning
On sites that existed before upgrading to v4.x, vhost generation is **disabled by default** so the
upgrade does not overwrite a working configuration. You must review and enable the new template per
site before Vito will regenerate the vhost. See
[Custom VHost Configuration](/docs/4.x/prologue/breaking-changes) for details, and the site **Settings**
page to preview and enable the new template.
:::
