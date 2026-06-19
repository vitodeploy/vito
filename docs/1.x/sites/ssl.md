# SSL

:::warning
SSLs are experimental and we're still working on them. We recommend you to use E2E SSL Encryptions of Cloud providers such as Cloudflare.
:::

## Letsencrypt

You can generate free SSLs from Letsencrypt by using Vito.

## Custom SSLs

You can also install your own Certificate. Vito will ask for your Certificate and Keys and will install them on your server and Nginx.

:::info
Vito encrypts the input and stores in your Vito Instance's database.
:::

## SSL for Subdomains/Aliases

VitoDeploy optionally allows you to install SSLs for your subdomains and aliases. You can check this option when you're creating a new SSL.
