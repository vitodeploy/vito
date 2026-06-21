# SSL Certificates

## Introduction

The server's **SSL** section manages server-level certificates. These are certificates that live on the server and can be assigned to one or more [site domains](../sites/domains.md), including wildcard certificates that cover many subdomains at once.

:::info
For issuing a certificate for a single site domain, you can also use the per-domain SSL options on the
site's [Domains](../sites/domains.md#ssl-per-domain) page. Use this server-level section when you want
a wildcard certificate, a custom certificate, or a CSR.
:::

## Certificate Types

Vito supports three ways to create a server-level certificate:

- **Let's Encrypt (Wildcard)**: Vito issues a wildcard certificate (for example `*.example.com`) via Let's Encrypt using DNS validation. This requires a [DNS provider](../settings/dns-providers.md) connected for the domain.
- **Custom**: install a certificate you already have by pasting the certificate and its private key.
- **CSR**: generate a Certificate Signing Request on the server, which you submit to a certificate authority. Once you receive the signed certificate, you install it back into Vito.

## Creating a Certificate

From the server's SSL page, click to create a new certificate, pick the type, and provide the required details. Vito then generates or installs the certificate on the server in the background.

## Activating & Assigning

After a certificate is created you can **activate** it and assign it to the matching site domains. When you choose **Custom Certificate** for a site domain, Vito lists the server-level certificates that match that domain, including wildcards.

## Downloading

You can download a server-level certificate (for example a generated CSR) from its actions menu.

## Renewal

Wildcard Let's Encrypt certificates are renewed automatically when they are within 30 days of expiry, provided the domain is still reachable through a configured DNS provider. Custom certificates are not auto-renewed — you must re-install a new certificate before the old one expires.

## Force HTTPS

Issuing a certificate does not by itself redirect HTTP traffic to HTTPS. To force HTTPS for a site, enable [Force SSL](../sites/settings.md#force-ssl) on the site's Settings page.
