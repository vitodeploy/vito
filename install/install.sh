#!/bin/bash

export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a
export V_USERNAME=vito
export V_PASSWORD=$(openssl rand -base64 12)

if [[ -z "${V_DOMAIN}" ]]; then
  echo "Error: V_DOMAIN environment variable is not set."
  exit 1
fi

if [[ -z "${V_ADMIN_EMAIL}" ]]; then
  echo "Error: V_ADMIN_EMAIL environment variable is not set."
  exit 1
fi

if [[ -z "${V_ADMIN_PASSWORD}" ]]; then
  echo "Error: V_ADMIN_PASSWORD environment variable is not set."
  exit 1
fi

apt remove needrestart -y

useradd -p $(openssl passwd -1 ${V_PASSWORD}) ${V_USERNAME}
usermod -aG ${V_USERNAME}
echo "${V_USERNAME} ALL=(ALL) NOPASSWD:ALL" | tee -a /etc/sudoers
mkdir /home/${V_USERNAME}
mkdir /home/${V_USERNAME}/.ssh
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}
chsh -s /bin/bash "${V_USERNAME}"
su - "${V_USERNAME}" -c "ssh-keygen -t rsa -N '' -f ~/.ssh/id_rsa" <<<y

# upgrade
apt clean
apt update
apt upgrade -y
apt autoremove -y

# requirements
apt install -y software-properties-common curl zip unzip git gcc

# nodejs
curl -fsSL https://deb.nodesource.com/setup_lts.x | -E bash -
apt update
apt install nodejs -y

# certbot
apt install certbot python3-certbot-nginx -y

# nginx
export V_NGINX_CONFIG="
    user ${V_USERNAME};
    worker_processes auto;
    pid /run/nginx.pid;
    include /etc/nginx/modules-enabled/*.conf;
    events {
        worker_connections 768;
    }
    http {
        sendfile on;
        tcp_nopush on;
        tcp_nodelay on;
        keepalive_timeout 65;
        types_hash_max_size 2048;
        include /etc/nginx/mime.types;
        default_type application/octet-stream;
        ssl_protocols TLSv1 TLSv1.1 TLSv1.2; # Dropping SSLv3, ref: POODLE
        ssl_prefer_server_ciphers on;
        access_log /var/log/nginx/access.log;
        error_log /var/log/nginx/error.log;
        gzip on;
        include /etc/nginx/conf.d/*.conf;
        include /etc/nginx/sites-enabled/*;
    }
"
apt install nginx -y
if ! echo "${V_NGINX_CONFIG}" | tee /etc/nginx/nginx.conf; then
    echo "Can't configure nginx!" && exit 1
fi
service nginx start

# php
export V_PHP_VERSION="8.1"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php${V_PHP_VERSION} php${V_PHP_VERSION}-fpm php${V_PHP_VERSION}-mbstring php${V_PHP_VERSION}-mysql php${V_PHP_VERSION}-mcrypt php${V_PHP_VERSION}-gd php${V_PHP_VERSION}-xml php${V_PHP_VERSION}-curl php${V_PHP_VERSION}-gettext php${V_PHP_VERSION}-zip php${V_PHP_VERSION}-bcmath php${V_PHP_VERSION}-soap php${V_PHP_VERSION}-redis
if ! sed -i "s/www-data/${V_USERNAME}/g" /etc/php/${V_PHP_VERSION}/fpm/pool.d/www.conf; then
    echo 'Error installing PHP' && exit 1
fi
service php${V_PHP_VERSION}-fpm enable
service php${V_PHP_VERSION}-fpm start
apt install -y php${V_PHP_VERSION}-ssh2
service php${V_PHP_VERSION}-fpm restart

# composer
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# database
export V_MARIADB_VERSION="10.3"
export V_DB_USER="vito"
export V_DB_NAME="vito"
export V_DB_PASS=$(openssl rand -base64 12)
wget https://downloads.mariadb.com/MariaDB/mariadb_repo_setup
chmod +x mariadb_repo_setup
./mariadb_repo_setup --mariadb-server-version="mariadb-${V_MARIADB_VERSION}"
apt update
apt install mariadb-server mariadb-backup -y
service mysql start
mysql -e "CREATE DATABASE IF NOT EXISTS ${V_DB_NAME} CHARACTER SET utf8 COLLATE utf8_general_ci"
mysql -e "CREATE USER IF NOT EXISTS '${V_DB_USER}'@'localhost' IDENTIFIED BY '${V_DB_PASS}'"
mysql -e "FLUSH PRIVILEGES"
mysql -e "GRANT ALL PRIVILEGES ON ${V_DB_NAME}.* TO '${V_DB_USER}'@'localhost'"
mysql -e "FLUSH PRIVILEGES"

# setup website
export V_SSL=1
export COMPOSER_ALLOW_SUPERUSER=1
export V_REPO="https://github.com/vitodeploy/vito.git"
export V_VHOST_CONFIG="
server {
    listen 80;
    listen [::]:80;
    server_name ${V_DOMAIN};
    root /home/${V_USERNAME}/${V_DOMAIN}/public;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${V_PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
"
rm -rf /home/${V_USERNAME}/${V_DOMAIN}
mkdir /home/${V_USERNAME}/${V_DOMAIN}
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}/${V_DOMAIN}
chmod -R 755 /home/${V_USERNAME}/${V_DOMAIN}
echo "${V_VHOST_CONFIG}" | tee /etc/nginx/sites-available/${V_DOMAIN}
ln -s /etc/nginx/sites-available/${V_DOMAIN} /etc/nginx/sites-enabled/
service nginx restart
rm -rf /home/${V_USERNAME}/${V_DOMAIN}
git config --global core.fileMode false
git clone ${V_REPO} /home/${V_USERNAME}/${V_DOMAIN}
find /home/${V_USERNAME}/${V_DOMAIN} -type d -exec chmod 755 {} \;
find /home/${V_USERNAME}/${V_DOMAIN} -type f -exec chmod 644 {} \;
cd /home/${V_USERNAME}/${V_DOMAIN} && git config core.fileMode false
cd /home/${V_USERNAME}/${V_DOMAIN} && composer install --no-dev
cp .env.prod .env
if [[ ${V_SSL} == 1 ]]; then
  export V_URL="https://${V_DOMAIN}"
else
  export V_URL="http://${V_DOMAIN}"
fi
sed -i "s|APP_URL=.*|APP_URL=${V_URL}|" /home/${V_USERNAME}/${V_DOMAIN}/.env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${V_DB_NAME}|" /home/${V_USERNAME}/${V_DOMAIN}/.env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${V_DB_USER}|" /home/${V_USERNAME}/${V_DOMAIN}/.env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${V_DB_PASS}|" /home/${V_USERNAME}/${V_DOMAIN}/.env
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan user:create Vito ${V_ADMIN_EMAIL} ${V_ADMIN_PASSWORD}
openssl genpkey -algorithm RSA -out /home/${V_USERNAME}/${V_DOMAIN}/storage/ssh-private.pem
chmod 600 /home/${V_USERNAME}/${V_DOMAIN}/storage/ssh-private.pem
ssh-keygen -y -f /home/${V_USERNAME}/${V_DOMAIN}/storage/ssh-private.pem > /home/${V_USERNAME}/${V_DOMAIN}/storage/ssh-public.key
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}/${V_DOMAIN}/storage/ssh-private.pem
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}/${V_DOMAIN}/storage/ssh-public.key

# setup supervisor
export V_WORKER_CONFIG="
[program:worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/${V_USERNAME}/${V_DOMAIN}/artisan queue:work --sleep=3 --backoff=0 --queue=default,ssh,ssh-long --timeout=3600 --tries=1
autostart=1
autorestart=1
user=vito
numprocs=1
redirect_stderr=true
stdout_logfile=/home/${V_USERNAME}/.logs/workers/worker.log
stopwaitsecs=3600
"
apt-get install supervisor -y
service supervisor enable
service supervisor start
mkdir -p /home/${V_USERNAME}/.logs
mkdir -p /home/${V_USERNAME}/.logs/workers
touch /home/${V_USERNAME}/.logs/workers/worker.log
echo "${V_WORKER_CONFIG}" | tee /etc/supervisor/conf.d/worker.conf
supervisorctl reread
supervisorctl update
supervisorctl start worker:*

# cleanup
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}

echo "🎉 Congratulations!"
echo "✅ SSH User: ${V_USERNAME}"
echo "✅ SSH Password: ${V_PASSWORD}"
echo "✅ DB Name: ${V_DB_NAME}"
echo "✅ DB Username: ${V_DB_USER}"
echo "✅ DB Password: ${V_DB_PASS}"
echo "✅ Admin Email: ${V_ADMIN_EMAIL}"
echo "✅ Admin Password: ${V_ADMIN_PASSWORD}"
echo "✅ URL: http://${V_DOMAIN}"
