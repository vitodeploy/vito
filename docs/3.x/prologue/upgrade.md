# Upgrade Guide

:::warning
Before upgrade first make a backup of `/home/vito/storage` folder and the `.env` file for VPS installations and the
volumes for the docker
installations.
:::

## Upgrading to 3.x from 2.x

## Upgrade Docker Installation

If you're using the latest tag, just do the [Update](../getting-started/update#update-docker) steps.

If you're using the `2.x` tag, You need to change it to `3.x` or `latest` tag.

:::info
`3.x` tag is the latest code on the `3.x` branch, which might not be stable yet, but it will be soon.

`latest` tag is the latest release of VitoDeploy from the `3.x` branch, which is stable and recommended for production
use.

We recommend using the `latest`.
:::

:::warning
It is required to have `APP_URL` environment variable set to your Vito URL, otherwise, some features like Vito Logs won't work properly.
:::

## Upgrade VPS Installation

You can upgrade your Vito instance from 2.x to 3.x in Automatic or Manual mode.

### Automatic Upgrade

Run the following command as `vito` user:

```sh
bash <(curl -Ls https://raw.githubusercontent.com/vitodeploy/vito/3.x/scripts/upgrade-2x-to-3x.sh)
```

### Manual Upgrade

SSH to your Vito instance with user `vito` and continue the steps:

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

**Install PHP 8.4:**

```sh
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4 php8.4-fpm php8.4-mbstring php8.4-mcrypt php8.4-gd php8.4-xml php8.4-curl php8.4-gettext php8.4-zip php8.4-bcmath php8.4-soap php8.4-redis php8.4-sqlite3 php8.4-intl
sudo sed -i "s/www-data/vito/g" /etc/php/8.4/fpm/pool.d/www.conf
sudo service php8.4-fpm enable
sudo service php8.4-fpm start
sudo apt install -y php8.4-ssh2
sudo service php8.4-fpm restart
sudo sed -i "s/memory_limit = .*/memory_limit = 1G/" /etc/php/8.4/fpm/php.ini
sudo sed -i "s/upload_max_filesize = .*/upload_max_filesize = 1G/" /etc/php/8.4/fpm/php.ini
sudo sed -i "s/post_max_size = .*/post_max_size = 1G/" /etc/php/8.4/fpm/php.ini
```

**Install Redis:**

Vito version 3.x uses Redis for caching, sessions, and queues. You need to install Redis server on your VPS.

```sh
sudo apt install redis-server -y
sudo service redis enable
sudo service redis start
```

**Install Node.js:**

Vito v3 uses Inertia.js, which requires Node.js to be installed.

```sh
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

**Adjust the Nginx configuration:**

Set php-fpm to use PHP 8.4 instead of PHP 8.2 by running the following command:

```sh
sudo sed -i "s/php8.2-fpm.sock/php8.4-fpm.sock/g" /etc/nginx/sites-available/vito
```

Add the following to the `/etc/nginx/sites-available/vito` as well:

```nginx
    location ~ \.php$ {
        ...
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        ...
    }
```

You can run this command instead

```sh
sudo sed -i '/location ~ \\\.php\$ {/a \    fastcgi_buffers 16 16k;\n    fastcgi_buffer_size 32k;' /etc/nginx/sites-available/vito
```

:::warning
Make sure to confirm the change by opening the file `/etc/nginx/sites-available/vito`
:::

Vito v3 transfers more data on the request headers because of the Inertia.js, so you need to increase the `client_max_body_size`.

Open `/etc/nginx/sites-available/vito` and add `client_max_body_size 100M;` line inside the `server` block.

This increase is necessary to allow you to export and import larger Vito settings.

```nginx
server {
    ...
    client_max_body_size 100M;
    ...
}
```

Then restart Nginx:

```sh
sudo service nginx restart
```

**Update supervisor configuration**

Vito now uses [Laravel Horizon](https://laravel.com/docs/12.x/horizon) for managing queues, so you need to update the supervisor configuration.

You need to update `/etc/supervisor/conf.d/worker.conf` file to use Horizon instead of the worker.

```sh
sudo sed -i 's/command=php \/home\/vito\/vito\/artisan queue:work --sleep=3 --backoff=0 --queue=default,ssh,ssh-long --timeout=3600 --tries=1/command=php \/home\/vito\/vito\/artisan horizon/' /etc/supervisor/conf.d/worker.conf
```

Or do it manually by setting `command` to:

```sh
command=php /home/vito/vito/artisan horizon
```

Then restart the supervisor:

```sh
sudo service supervisor restart
```

**Pull the latest changes:**

```sh
git fetch
```

**Switch to the `3.x` branch:**

```sh
git checkout 3.x
```

**Run the update script:**

```sh
bash scripts/update.sh
```

### Upgrade failed?

If the upgrade (automatic or manual) failed, follow these steps to fix it:

1. [Install a new Vito instance](../getting-started/installation.mdx#install-on-vps)
2. Copy the `.env` file from the old instance backup to the new instance.
3. Copy the `storage` folder from the old instance backup to the new instance.
4. Run `bash scripts/update.sh` on the new instance to apply the changes.

## Upgrade Local installation

If you're using Laravel Sail, you need to kill the current container and delete its images and then boot sail up again.

[Read the installation documentation](../getting-started/installation.mdx#laravel-sail) for more information.

If you're using other tools like Laravel Valet or etc., You need to make sure you have PHP 8.4 installed and then switch
to the `3.x` branch.

:::warning
Make sure you set the `REDIS_HOST=redis` environment variable in your `.env` file.
:::