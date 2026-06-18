# Application

## Introduction

In the Application page you can manage your app and deployments.

## Site alerts

Vito surfaces **site alerts** to flag conditions that need your attention. They appear at the top of
the site's pages and on the sites listing, and most include a call to action so you can resolve them
quickly. Alerts update in real time and clear automatically once the underlying condition is
resolved. While a site is still installing, alerts are suppressed.

The following alerts can appear:

### Installation failed

Shown when a site's installation did not complete. The alert includes the step it failed on and the
error output, and offers a **Retry installation** action. Retrying re-runs the installation, and
steps that already completed (isolated user, vhost, cloned repository, deployed key) are detected and
skipped.

### Pending domains

Shown when one or more of the site's [domains](/docs/4.x/sites/domains) do not resolve to the server.
It lists the affected domains and links to **Manage Domains**, where you can fix your DNS records or
force activation.

### SSL is disabled

Shown when the site is not served over HTTPS even though valid certificates may exist. Provides an
**Enable SSL** action to start serving the site securely.

### SSL expiring

Shown when one or more of the site's SSL certificates will expire within 14 days. It lists the
affected domains and the time remaining, and links to **Manage Domains**.

### VHost generation is disabled

Shown when automatic vhost generation is turned off for the site, which means changes to SSL,
domains, or redirects will not update the vhost. It links to the [Settings](/docs/4.x/sites/settings)
page to review the template, and offers a **Re-enable** action. This alert is expected on sites
upgraded from an earlier version, where generation is disabled by default.

### Site needs first deploy

Shown for [reverse proxy sites](#reverse-proxy-sites) (such as Node and Bun) that have not yet had a
successful deployment. The application worker is created on the first successful deploy, so this
alert links to **Application** to prompt you to deploy.

## Reverse proxy sites

Reverse proxy site types, such as [Node](/docs/4.x/sites/site-types) and Bun, run a long-lived
process behind the webserver. For these sites the Application page shows a header card with the key
details of that process:

- **Port** - the port your app listens on. The webserver proxies requests to this port. It must be a
  non-privileged port (1024-65535), and you can change it here.
- **Start command** - the command used to launch your app (for example `node server.js`). If you do
  not set one, the site type's default is used.
- **Worker** - the status of the [worker](/docs/4.x/servers/workers) that runs your app. Vito creates
  this worker automatically so your start command keeps running and restarts if it stops. From the
  menu you can start, stop, or restart the worker and view its logs.

The worker status updates in real time. It begins as `pending_deploy` until the worker has been
created during the first deployment.

## Branch

You can change the branch of your cloned repository

## Deployment Script

This is a script which will be executed on your application server and in the site's path each time you press the `Deploy` button.

:::info
Your website's path is `/home/vito/YOUR-DOMAIN`
:::

Vito also exports some variables when running your deployment script, and you can use them during the deployment.

Here are the supported variables:

```
SITE_PATH=[path to the website]
DOMAIN=[domain name]
BRANCH=[branch name]
REPOSITORY=[repository]
COMMIT_ID=[commit id when deploying]
PHP_VERSION=[the php version that your site running]
PHP_PATH=[path to the php your site running]
```

The variables are bash script variables and you can use them just like a normal bash script variable.

here is an example:

```shell
echo "Deploying $DOMAIN to $SITE_PATH"
```

Example deployment script for a Laravel application:

```bash
cd $SITE_PATH

php artisan down

git pull origin $BRANCH

composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force

php artisan optimize:clear
php artisan optimize

sudo service supervisor restart

php artisan up

echo "✅ Deployment completed successfully!"
```

## Environment variables

You can update `.env` file of your application using `Update .env` button.

By default, Vito will read the `.env` file from your site's root directory. However, you can change the path to the `.env` file when updating it via Vito.

## Deploy

This button appears when there is a deployment script! So you need to first write your deployment script and then this
button will appear, and you will be able to click on it an execute the deployment script on the server.

## Auto Deployment

You can enable auto deployment for your application by setting up git hooks.

Vito will handle the git hooks setup for you and you just need to click a button to enable it.

:::warning
You need to have a deployment script to enable auto deployment.
:::

:::warning
Since source control providers need to send a request to your server, you need to have vito accessible in the Internet.
This feature
cannot work when you use Vito locally.
:::
