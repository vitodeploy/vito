#!/bin/bash

export VITO_VERSION="1.x"
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a

if [[ -z "${V_USERNAME}" ]]; then
  export V_USERNAME=vito
fi

if [[ -z "${V_PASSWORD}" ]]; then
  export V_PASSWORD=$(openssl rand -base64 12)
fi

if [[ -z "${V_ADMIN_EMAIL}" ]]; then
  echo "Enter your email address:"
  read V_ADMIN_EMAIL
fi

if [[ -z "${V_ADMIN_EMAIL}" ]]; then
  echo "Error: V_ADMIN_EMAIL environment variable is not set."
  exit 1
fi

if [[ -z "${V_ADMIN_PASSWORD}" ]]; then
  echo "Enter a password for Vito's dashboard:"
  read V_ADMIN_PASSWORD
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
export V_PHP_VERSION="8.2"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php${V_PHP_VERSION} php${V_PHP_VERSION}-fpm php${V_PHP_VERSION}-mbstring php${V_PHP_VERSION}-mcrypt php${V_PHP_VERSION}-gd php${V_PHP_VERSION}-xml php${V_PHP_VERSION}-curl php${V_PHP_VERSION}-gettext php${V_PHP_VERSION}-zip php${V_PHP_VERSION}-bcmath php${V_PHP_VERSION}-soap php${V_PHP_VERSION}-redis php${V_PHP_VERSION}-sqlite3
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

# setup website
export COMPOSER_ALLOW_SUPERUSER=1
export V_REPO="https://github.com/vitodeploy/vito.git"
export V_VHOST_CONFIG="
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /home/${V_USERNAME}/vito/public;

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
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
"
rm -rf /home/${V_USERNAME}/vito
mkdir /home/${V_USERNAME}/vito
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}/vito
chmod -R 755 /home/${V_USERNAME}/vito
rm /etc/nginx/sites-available/default
rm /etc/nginx/sites-enabled/default
echo "${V_VHOST_CONFIG}" | tee /etc/nginx/sites-available/vito
ln -s /etc/nginx/sites-available/vito /etc/nginx/sites-enabled/
service nginx restart
rm -rf /home/${V_USERNAME}/vito
git config --global core.fileMode false
git clone -b ${VITO_VERSION} ${V_REPO} /home/${V_USERNAME}/vito
find /home/${V_USERNAME}/vito -type d -exec chmod 755 {} \;
find /home/${V_USERNAME}/vito -type f -exec chmod 644 {} \;
cd /home/${V_USERNAME}/vito && git config core.fileMode false
cd /home/${V_USERNAME}/vito
git checkout $(git tag -l --merged ${VITO_VERSION} --sort=-v:refname | head -n 1)
composer install --no-dev
cp .env.prod .env
touch /home/${V_USERNAME}/vito/storage/database.sqlite
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan user:create Vito ${V_ADMIN_EMAIL} ${V_ADMIN_PASSWORD}
openssl genpkey -algorithm RSA -out /home/${V_USERNAME}/vito/storage/ssh-private.pem
chmod 600 /home/${V_USERNAME}/vito/storage/ssh-private.pem
ssh-keygen -y -f /home/${V_USERNAME}/vito/storage/ssh-private.pem > /home/${V_USERNAME}/vito/storage/ssh-public.key
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}/vito/storage/ssh-private.pem
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}/vito/storage/ssh-public.key

# setup supervisor
export V_WORKER_CONFIG="
[program:worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/${V_USERNAME}/vito/artisan queue:work --sleep=3 --backoff=0 --queue=default,ssh,ssh-long --timeout=3600 --tries=1
autostart=1
autorestart=1
user=vito
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

# setup cronjobs
echo "* * * * * cd /home/${V_USERNAME}/vito && php artisan schedule:run >> /dev/null 2>&1" | sudo -u ${V_USERNAME} crontab -

# cleanup
chown -R ${V_USERNAME}:${V_USERNAME} /home/${V_USERNAME}

# cache
php artisan config:cache

# start worker
supervisorctl start worker:*

# print info
echo "🎉 Congratulations!"
echo "✅ SSH User: ${V_USERNAME}"
echo "✅ SSH Password: ${V_PASSWORD}"
echo "✅ Admin Email: ${V_ADMIN_EMAIL}"
echo "✅ Admin Password: ${V_ADMIN_PASSWORD}"
