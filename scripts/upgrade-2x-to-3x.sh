#!/bin/bash

echo "Upgrading Vito from 2.x to 3.x"

echo "Getting started"
cd /home/vito/vito
git stash
sudo chown -R vito:vito /home/vito/vito

echo "Installing PHP 8.4"
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
sudo sed -i '/location ~ \\\.php\$ {/a \    fastcgi_buffers 16 16k;\n    fastcgi_buffer_size 32k;' /etc/nginx/sites-available/vito

echo "Installing Redis"
sudo apt install redis-server -y
sudo service redis enable
sudo service redis start

echo "Installing Node.js"
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

echo "Configuring Nginx"
sudo sed -i "s/php8.2-fpm.sock/php8.4-fpm.sock/g" /etc/nginx/sites-available/vito
sudo sed -i '/server\s*{.*/a \    client_max_body_size 100M;' /etc/nginx/sites-available/vito
sudo service nginx restart

echo "Update supervisor configuration"
sudo sed -i 's/command=php \/home\/vito\/vito\/artisan queue:work --sleep=3 --backoff=0 --queue=default,ssh,ssh-long --timeout=3600 --tries=1/command=php \/home\/vito\/vito\/artisan horizon/' /etc/supervisor/conf.d/worker.conf
sudo service supervisor restart

echo "Fetching the latest release"
git fetch
git checkout 3.x
bash scripts/update.sh
