# Upgrade

:::warning
Before upgrade first make a backup of your database and storage folder.
:::

## Upgrading to 1.x from 0.x

SSH to your Vito instance with user `vito` and continue the steps:

### PHP Upgrade

Run the following command to install PHP `8.2` on your Vito instance:

```sh
sudo apt install -y php8.2 php8.2-fpm php8.2-mbstring php8.2-gd php8.2-xml php8.2-curl php8.2-gettext php8.2-zip php8.2-bcmath php8.2-soap php8.2-redis php8.2-sqlite3 php8.2-mysql
```

And then

```sh
sudo sed -i "s/www-data/vito/g" /etc/php/8.2/fpm/pool.d/www.conf
```

And then

```sh
sudo sed -i "s/php8.1/php8.2/g" /etc/nginx/sites-available/YOUR-DOMAIN
```

And restart PHP and Nginx

```sh
sudo service nginx restart
sudo service php8.2-fpm restart
```

**Replace YOUR-DOMAIN with your Vito's domain!**

### Switch to 1.x branch

Run the following commands to switch to the version 1.x

```sh
cd /home/vito/YOUR-VITO-DOMAIN
git pull
git switch 1.x
```

### Update Composer Dependencies

Update the Composer dependencies by running this command:

```sh
composer install --no-dev
```

### Database Driver Update

VitoDeploy now uses Sqlite3 as its database driver.

Switch the database by running the following command:

```sh
php /home/vito/YOUR-VITO-DOMAIN/artisan migrate-from-mysql-to-sqlite
```

### Cleanup the permissions

If you haven't ran the commands via `vito` user then you need to cleanup the permissions:

```sh
sudo chown -R vito:vito /home/vito/YOUR-VITO-DOMAIN \
    && chmod -R 755 /home/vito/YOUR-VITO-DOMAIN/storage /home/vito/YOUR-VITO-DOMAIN/bootstrap/cache
```

### Restart Worker

And finally restart the worker.

```sh
sudo supervisorctl restart worker:*
```

### Stop Mysql

You don't need Mysql anymore. so either you can uninstall it or disable it with the following commands:

```sh
sudo service mysql stop
sudo systemctl disable mysql
```