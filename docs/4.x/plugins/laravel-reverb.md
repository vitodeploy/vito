# Laravel Reverb Plugin

:::warning
**The Laravel Reverb plugin is deprecated and will be removed in a future release.**

You no longer need a dedicated plugin to run Laravel Reverb. With WebSocket support now built into
[site redirects](../sites/redirects.md), you can set up a Reverb deployment using Vito's core features. See
[Setting up Laravel Reverb manually](#setting-up-laravel-reverb-manually) below for the recommended approach.
:::

## Setting up Laravel Reverb manually

This is the recommended way to run Laravel Reverb on Vito. It uses only core features — a Laravel site, a worker, and a
proxy redirect with WebSocket support — so you don't depend on the deprecated plugin.

In the steps below, replace `{domain}` with your site's domain and `{reverb port}` with the internal port your Reverb
server will listen on (for example `8089`). This internal port is never exposed to the internet; the web server proxies
to it.

### 1. Create a Laravel site

Create a new **Laravel** site as described in [Create a Site](../sites/create.md).

### 2. Configure your `.env`

Add the following Reverb variables to your application's `.env`:

```dotenv
REVERB_HOST={domain}
REVERB_PORT=443            # use 443 for https, or 80 for http
REVERB_SCHEME=https        # use https, or http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT={reverb port}
```

`REVERB_HOST`, `REVERB_PORT` and `REVERB_SCHEME` describe the public endpoint your clients connect to (through the web
server). `REVERB_SERVER_HOST` and `REVERB_SERVER_PORT` are where the Reverb server itself listens locally — the host
must be `0.0.0.0` and the port is the internal `{reverb port}`.

### 3. Update your deployment script

Add the following command to your site's [deployment script](../sites/application.md) so Reverb picks up new code on
every deploy:

```bash
php artisan reverb:restart
```

### 4. Create a worker

Create a [worker](../servers/workers.md) to keep the Reverb server running:

- **Command**: `php artisan reverb:start --port={reverb port}`
- **User**: the site's [isolated user](../sites/isolation.md)
- **Numprocs**: `1`

:::warning
`Numprocs` must be `1`. Running multiple Reverb processes on the same port will not work.
:::

### 5. Create a proxy redirect with WebSocket support

Create a [redirect](../sites/redirects.md) to proxy WebSocket traffic to the Reverb server:

- **Mode**: `Proxy`
- **From**: `~ ^/app(s)?/`
- **To**: `http://0.0.0.0:{reverb port}`
- **WebSocket support**: `Yes`

### 6. Redeploy

Redeploy the application, and everything should be working fine.

## Migrating an existing Reverb site

If you created a site using the deprecated **Laravel Reverb** site type, convert it to a standard **Laravel** site and
set Reverb up using the [manual steps](#setting-up-laravel-reverb-manually) above.

Because the site type is provided by the plugin, the site's `type` must be changed back to `laravel`. Connect to the
database that **Vito itself** uses (not the database on your provisioned server) and run:

```sql
UPDATE sites SET type = 'laravel' WHERE type = 'laravel-reverb';
```

:::warning
Back up your database before running any manual SQL. The statement above converts **every** site currently using the
`laravel-reverb` type. To convert a single site, add `AND id = {site id}` to the `WHERE` clause.
:::

After converting the type, finish wiring the site up manually:

1. The proxy that the plugin injected into your vhost is no longer generated automatically. Create a
   [proxy redirect with WebSocket support](#5-create-a-proxy-redirect-with-websocket-support) to restore it — use the
   port your Reverb site was already running on.
2. Add the [`.env` variables](#2-configure-your-env) and the `php artisan reverb:restart` line to your
   [deployment script](#3-update-your-deployment-script).
3. The plugin already created a worker named `laravel-reverb` that runs the Reverb server; it will keep running. Review
   it against [step 4](#4-create-a-worker) and adjust the command or port if needed.
4. Redeploy the site.
