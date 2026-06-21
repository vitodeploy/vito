# Upgrade Guide

:::warning
Before upgrade first make a backup of `/home/vito/storage` folder and the `.env` file for VPS installations and the
volumes for the docker
installations.
:::

## Upgrading to 4.x from 3.x

:::danger
v4.x ships with **breaking changes**. Most importantly, the vhost template engine moved from Blade to
Mustache, and to avoid breaking live sites, vhost regeneration is **disabled by default on every
existing site** until you review and enable the new template. Read the
[Breaking Changes](./breaking-changes) guide **before** you upgrade.
:::

:::info
Vito 4.x keeps the same server stack as 3.x (PHP 8.4, Nginx, PHP-FPM, Redis, Supervisor), so no PHP,
Redis, or Node.js changes are required when upgrading from 3.x. The only new piece of infrastructure
is the **WebSocket server** that powers the live terminal and realtime updates.
:::

## Upgrade Docker Installation

If you're using the `latest` tag, just do the [Update](../getting-started/update#update-docker) steps.

If you're using the `3.x` tag, you need to change it to `4.x` or the `latest` tag.

:::info
`4.x` tag is the latest code on the `4.x` branch, which might not be stable yet, but it will be soon.

`latest` tag is the latest release of VitoDeploy from the `4.x` branch, which is stable and recommended
for production use.

We recommend using the `latest`.
:::

:::info
`WS_BROADCAST_SECRET` is optional but **recommended for production**. It is used to authenticate
realtime WebSocket broadcasts. If you do not set it, Vito falls back to `APP_KEY` and logs a
deprecation warning. See
[Configuration](../getting-started/configuration#ws_broadcast_secret-recommended) for details.
:::

## Upgrade VPS Installation

You can upgrade your Vito instance from 3.x to 4.x in Automatic or Manual mode.

:::warning
Run the VPS upgrade as the `vito` user — the same user Vito is installed and runs under — **not** as
`root`. For a manual upgrade, run every command from inside the project directory, `/home/vito/vito`.
(The automatic script changes into that directory for you.)
:::

### Automatic Upgrade

Run the following command as the `vito` user:

```sh
bash <(curl -Ls https://raw.githubusercontent.com/vitodeploy/vito/4.x/scripts/upgrade-3x-to-4x.sh)
```

:::warning
While 4.x is still in **beta**, there is no stable `4.x` release tag yet, so you must pass the `--beta`
flag to upgrade to the latest beta release:

```sh
bash <(curl -Ls https://raw.githubusercontent.com/vitodeploy/vito/4.x/scripts/upgrade-3x-to-4x.sh) --beta
```

Once 4.x is released as stable, run the command without any flag.
:::

The script will:

- Configure Nginx with the `/ws/` WebSocket proxy (validated with `nginx -t` before reloading).
- Add a `websocket` Supervisor worker that runs `php artisan ws:serve`.
- Switch to the `4.x` branch and run the standard update (Composer install, database migrations, cache
  rebuild, and worker restart).

### Manual Upgrade

SSH into your Vito instance as the `vito` user and continue the steps.

Go to the root of the project:

```sh
cd /home/vito/vito
```

**Discard all the possible changes to the code base:**

```sh
git stash
```

**Fix any possible ownership change to the code base:**

```sh
sudo chown -R vito:vito /home/vito/vito
```

**Add the WebSocket proxy to Nginx:**

v4.x serves the live terminal and realtime updates through a WebSocket server listening on
`127.0.0.1:8085`. Add the following `location` block inside the `server { ... }` block of
`/etc/nginx/sites-available/vito`:

```nginx
    location /ws/ {
        proxy_pass http://127.0.0.1:8085;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_read_timeout 86400;
    }
```

Then test the configuration and restart Nginx:

```sh
sudo nginx -t
sudo service nginx restart
```

**Add the WebSocket Supervisor worker:**

```sh
sudo mkdir -p /home/vito/.logs/workers
sudo touch /home/vito/.logs/workers/websocket.log
sudo chown -R vito:vito /home/vito/.logs

echo "
[program:websocket]
process_name=%(program_name)s
command=php /home/vito/vito/artisan ws:serve
autostart=1
autorestart=1
user=vito
redirect_stderr=true
stdout_logfile=/home/vito/.logs/workers/websocket.log
" | sudo tee /etc/supervisor/conf.d/websocket.conf

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start websocket
```

**Pull the latest changes:**

```sh
git fetch
```

**Switch to the `4.x` branch:**

```sh
git checkout 4.x
```

**Run the update script:**

```sh
bash scripts/update.sh
```

:::warning
While 4.x is in beta, pass the `--beta` flag so the updater picks up the latest beta release instead of
looking for a (not yet existing) stable tag:

```sh
bash scripts/update.sh --beta
```
:::

### Upgrade failed?

If the upgrade (automatic or manual) failed, follow these steps to fix it:

1. [Install a new Vito instance](../getting-started/installation#install-on-vps)
2. Copy the `.env` file from the old instance backup to the new instance.
3. Copy the `storage` folder (`/home/vito/vito/storage`) from the old instance backup to the new instance.
4. Run `bash scripts/update.sh` on the new instance to apply the changes.

## Upgrade Local Installation

Local installation via Laravel Sail is no longer supported in 4.x. Use the
[Docker installation](../getting-started/installation#install-via-docker) instead.

If you run Vito locally another way (Laravel Valet, etc.), make sure you have PHP 8.4 or newer
installed, switch to the `4.x` branch, and review the [Breaking Changes](./breaking-changes).
