# SSL

## Introduction

Vito supports SSL installation for your sites. It can issue a Letsencrypt Certificate for your website, or you can
install your own Certificate.

:::warning
Any actions related to SSLs will regenerate the Nginx vhost file and any manual changes to the Nginx vhost will be lost.
:::

## Letsencrypt

Let’s Encrypt is a free, automated, and open certificate authority (CA) that provides digital certificates to enable
HTTPS (SSL/TLS) for websites. It simplifies the process of securing websites by offering an automated mechanism to
issue, renew, and manage certificates without manual intervention.

:::info
Vito uses `certbot` to issue a certificate for your domain. certbot requires an email address for the certificate and
Vito uses your user's email address for this.
:::

## Custom SSLs

You can also install your own Certificate. Vito will ask for your Certificate and Keys and will install them on your
server and Nginx.

:::info
Vito encrypts the input and stores in your Vito Instance's database.
:::

## SSL for Subdomains/Aliases

VitoDeploy optionally allows you to install SSLs for your subdomains and aliases. You can check this option when you're
creating a new SSL.

## Multiple SSLs

You can add multiple SSLs to your server for a website and activate any of them at any time.

## Force HTTPS/SSL

If you already have an SSL installed and activated, you can click the `Force SSL` button to redirect all HTTP requests.

:::warning
Make sure one of the SSLs is activated before enabling this option.
:::