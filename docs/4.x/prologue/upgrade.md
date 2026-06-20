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

## Troubleshooting

This section lists problems you may run into while upgrading a server from 3.x to 4.x, and how to
resolve them.

### Expired MySQL APT signing key

When updating packages or checking for updates on a server where Vito 3.x installed MySQL, you may
see an error like this:

```text
W: GPG error: http://repo.mysql.com/apt/ubuntu noble InRelease: The following signatures were invalid: EXPKEYSIG B7B3B788A8D3785C MySQL Release Engineering <mysql-build@oss.oracle.com>
E: The repository 'http://repo.mysql.com/apt/ubuntu noble InRelease' is not signed.
```

This is caused by an **expired GPG key** for the MySQL APT repository that was imported when 3.x
installed MySQL. APT verifies a repository's release metadata against a stored signing key; once that
key passes its expiry date (`EXPKEYSIG` = *expired key signature*), APT treats the repository as
unsigned and refuses to use it, which blocks package updates.

To fix it, run the following command in the server's terminal as the `vito` user:

```sh
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/mysql-apt-config.gpg \
    --keyserver hkps://keyserver.ubuntu.com --recv-keys B7B3B788A8D3785C
```

**What this does and why it works:**

- `--no-default-keyring --keyring /usr/share/keyrings/mysql-apt-config.gpg` tells `gpg` to operate on
  the dedicated MySQL keyring file rather than your personal keyring. This is the exact keyring the
  MySQL APT source references with its `signed-by=` option, so it's the copy of the key that APT
  actually checks signatures against.
- `--keyserver hkps://keyserver.ubuntu.com --recv-keys B7B3B788A8D3785C` downloads the key with ID
  `B7B3B788A8D3785C` (the MySQL Release Engineering key named in the error) from the Ubuntu keyserver
  over an encrypted (`hkps`) connection.
- MySQL renewed this key under the **same key ID** by extending its expiry date and re-publishing it.
  Re-fetching it pulls the updated self-signature carrying the new expiry, overwriting the stale,
  expired copy in the keyring. With a valid, non-expired key in place, APT can verify the repository's
  signature again and `apt update` succeeds.

:::info
After importing the key, re-run the package update (or retry the operation in Vito) to confirm the
error is gone.
:::

### "Updates available" count never clears

You may see the *"X updates available"* warning and find that, no matter how many times you run the
update, the count never changes — it always reports the same number of updates remaining (for example,
3).

This can be caused by an incorrectly configured MySQL install. Check the upgrade log for a message
like this:

```text
The following packages have been kept back:
  libgd3 mysql-common mysql-server

0 upgraded, 0 newly installed, 0 to remove and 3 not upgraded.
```

"Kept back" means APT is intentionally holding those packages instead of upgrading them, so Vito's
automatic update never applies them and the count stays stuck.

If the held-back packages are MySQL needing an upgrade to the expected version, back up your databases
first, then perform the upgrade manually in the server's terminal. For example:

```sh
sudo apt-get install mysql-server mysql-common libgd3
```

:::warning
Take fresh backups **before** upgrading MySQL manually. Your 3.x database backups will **not** restore
on 4.x, so old backups are not a valid safety net here — see
[Breaking Changes › Database Backup Format](./breaking-changes#database-backup-format).
:::

If the manual upgrade causes any issues with the database, you can reinstall the associated SQL
instance from the server's **Services** page and then restore your databases from your fresh backups.

### Sites report "SSL certificate expiring in 0 days"

After upgrading, you may see a warning on all of your sites along the lines of *"X SSL certificate
expiring in 0 days"*, even though the certificates are valid.

This happens because Vito 3.x stored each certificate's expiry date when it was issued but never kept
it up to date, so after renewals the stored date became stale. Vito 4.x maintains these dates for you,
but it does so via an **overnight script**. The expiry dates will therefore refresh and the warnings
clear automatically the next time that script runs.

If you would rather clear the warning straight away, you can refresh the dates on demand per site:

1. Open the site and go to the **Domains** menu.
2. Click the **lock icon** at the top of the Domains menu.
3. Click **Check SSL expiry (all)**.

Vito will re-read the certificates' real expiry dates and update the warnings immediately.
