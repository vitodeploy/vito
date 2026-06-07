#!/bin/bash

echo "
 __      ___ _        _____             _
 \ \    / (_) |      |  __ \           | |
  \ \  / / _| |_ ___ | |  | | ___ _ __ | | ___  _   _
   \ \/ / | | __/ _ \| |  | |/ _ \ '_ \| |/ _ \| | | |
    \  /  | | || (_) | |__| |  __/ |_) | | (_) | |_| |
     \/   |_|\__\___/|_____/ \___| .__/|_|\___/ \__, |
                                 | |             __/ |
                                 |_|            |___/
"

export VITO_VERSION="${VITO_VERSION:-4.x}"
export VITO_CHANNEL="${VITO_CHANNEL:-release}"
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a

if [[ -z "${V_PASSWORD}" ]]; then
  export V_PASSWORD=$(openssl rand -base64 12)
fi

if [[ -z "${VITO_APP_URL}" ]]; then
  export DEFAULT_VITO_APP_URL=http://$(curl -s https://free.freeipapi.com -4)
  read -p "Enter the APP_URL [$DEFAULT_VITO_APP_URL]: " VITO_APP_URL
  export VITO_APP_URL=${VITO_APP_URL:-$DEFAULT_VITO_APP_URL}
  echo "APP_URL is set to: $VITO_APP_URL\n"
fi

if [[ -z "${V_ADMIN_EMAIL}" ]]; then
  read -p "Enter admin's email address: " V_ADMIN_EMAIL
fi

if [[ -z "${V_ADMIN_EMAIL}" ]]; then
  echo "Error: V_ADMIN_EMAIL environment variable is not set."
  exit 1
fi

if [[ -z "${V_ADMIN_PASSWORD}" ]]; then
  read -p "Enter a password for the admin user: " V_ADMIN_PASSWORD
fi

if [[ -z "${V_ADMIN_PASSWORD}" ]]; then
  echo "Error: V_ADMIN_PASSWORD environment variable is not set."
  exit 1
fi

apt remove needrestart -y

useradd -p $(openssl passwd -1 ${V_PASSWORD}) vito
usermod -aG vito
echo "vito ALL=(ALL) NOPASSWD:ALL" | tee -a /etc/sudoers
mkdir /home/vito
mkdir /home/vito/.ssh
chown -R vito:vito /home/vito
chsh -s /bin/bash "vito"
su - "vito" -c "ssh-keygen -t rsa -N '' -f ~/.ssh/id_rsa" <<< y

apt clean
rm -f /etc/apt/sources.list.d/*ondrej*php* 2>/dev/null || true
apt update
apt upgrade -y
apt autoremove -y

apt install -y software-properties-common curl zip unzip git gcc

apt install certbot python3-certbot-nginx -y

export V_NGINX_CONFIG="
    user vito;
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
        ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
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

export V_NODE_VERSION="20.x"
curl -fsSL https://deb.nodesource.com/setup_${V_NODE_VERSION} | sudo -E bash -
apt install -y nodejs

. /etc/os-release
export V_DISTRO_CODENAME="${UBUNTU_CODENAME:-${VERSION_CODENAME}}"

if curl -fsSL "https://ppa.launchpadcontent.net/ondrej/php/ubuntu/dists/${V_DISTRO_CODENAME}/Release" -o /dev/null; then
  export V_PHP_VERSION="8.4"
  add-apt-repository ppa:ondrej/php -y
  apt update
else
  echo "ondrej/php has no packages for '${V_DISTRO_CODENAME}'; using the distribution's PHP."
  apt update
  V_PHP_VERSION=$(apt-cache search --names-only '^php[0-9]+\.[0-9]+-fpm$' | grep -oE '^php[0-9]+\.[0-9]+-fpm' | grep -oE '[0-9]+\.[0-9]+' | sort -V | tail -n 1)
  export V_PHP_VERSION
fi

if [[ -z "${V_PHP_VERSION}" ]]; then
  echo "Error: could not determine a PHP version to install for '${V_DISTRO_CODENAME}'." && exit 1
fi

echo "Installing PHP ${V_PHP_VERSION}..."
apt install -y php${V_PHP_VERSION} php${V_PHP_VERSION}-fpm php${V_PHP_VERSION}-mbstring php${V_PHP_VERSION}-gd php${V_PHP_VERSION}-xml php${V_PHP_VERSION}-curl php${V_PHP_VERSION}-zip php${V_PHP_VERSION}-bcmath php${V_PHP_VERSION}-soap php${V_PHP_VERSION}-redis php${V_PHP_VERSION}-sqlite3 php${V_PHP_VERSION}-intl
if ! sed -i "s/www-data/vito/g" /etc/php/${V_PHP_VERSION}/fpm/pool.d/www.conf; then
  echo 'Error installing PHP' && exit 1
fi
systemctl enable php${V_PHP_VERSION}-fpm
service php${V_PHP_VERSION}-fpm start
service php${V_PHP_VERSION}-fpm restart
sed -i "s/memory_limit = .*/memory_limit = 1G/" /etc/php/${V_PHP_VERSION}/fpm/php.ini
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 1G/" /etc/php/${V_PHP_VERSION}/fpm/php.ini
sed -i "s/post_max_size = .*/post_max_size = 1G/" /etc/php/${V_PHP_VERSION}/fpm/php.ini

curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

apt install redis-server -y
service redis enable
service redis start

export COMPOSER_ALLOW_SUPERUSER=1
export V_REPO="https://github.com/vitodeploy/vito.git"
export V_VHOST_CONFIG="
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /home/vito/vito/public;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    client_max_body_size 100M;

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
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    location /ws/ {
        proxy_pass http://127.0.0.1:8085;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \"upgrade\";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_read_timeout 86400;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
"
rm -rf /home/vito/vito
mkdir /home/vito/vito
chown -R vito:vito /home/vito/vito
chmod -R 755 /home/vito/vito
rm /etc/nginx/sites-available/default
rm /etc/nginx/sites-enabled/default
echo "${V_VHOST_CONFIG}" | tee /etc/nginx/sites-available/vito
ln -s /etc/nginx/sites-available/vito /etc/nginx/sites-enabled/
service nginx restart
rm -rf /home/vito/vito
git config --global core.fileMode false
git clone -b ${VITO_VERSION} ${V_REPO} /home/vito/vito
find /home/vito/vito -type d -exec chmod 755 {} \;
find /home/vito/vito -type f -exec chmod 644 {} \;
cd /home/vito/vito && git config core.fileMode false
cd /home/vito/vito
if [[ "${VITO_CHANNEL}" == "release" ]]; then
  VITO_TAG=$(git tag -l --merged ${VITO_VERSION} --sort=-v:refname | head -n 1)

  if [[ -n "${VITO_TAG}" ]]; then
    git checkout ${VITO_TAG}
  else
    echo "No release tag found for ${VITO_VERSION}, using branch instead."
  fi
fi
composer install --no-dev
cp .env.prod .env
sed -i "s|^APP_URL=.*|APP_URL=${VITO_APP_URL}|" .env
touch /home/vito/vito/storage/database.sqlite
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan user:create Vito ${V_ADMIN_EMAIL} ${V_ADMIN_PASSWORD}
openssl genpkey -algorithm RSA -out /home/vito/vito/storage/ssh-private.pem
chmod 600 /home/vito/vito/storage/ssh-private.pem
ssh-keygen -y -f /home/vito/vito/storage/ssh-private.pem > /home/vito/vito/storage/ssh-public.key
chown -R vito:vito /home/vito/vito/storage/ssh-private.pem
chown -R vito:vito /home/vito/vito/storage/ssh-public.key

php artisan optimize

chown -R vito:vito /home/vito

export V_WORKER_CONFIG="
[program:worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/vito/vito/artisan horizon
autostart=1
autorestart=1
user=vito
redirect_stderr=true
stdout_logfile=/home/vito/.logs/workers/worker.log
stopwaitsecs=3600
"
apt-get install supervisor -y
service supervisor enable
service supervisor start
mkdir -p /home/vito/.logs
mkdir -p /home/vito/.logs/workers
touch /home/vito/.logs/workers/worker.log
echo "${V_WORKER_CONFIG}" | tee /etc/supervisor/conf.d/worker.conf

export V_WEBSOCKET_CONFIG="
[program:websocket]
process_name=%(program_name)s
command=php /home/vito/vito/artisan ws:serve
autostart=1
autorestart=1
user=vito
redirect_stderr=true
stdout_logfile=/home/vito/.logs/workers/websocket.log
"
touch /home/vito/.logs/workers/websocket.log
echo "${V_WEBSOCKET_CONFIG}" | tee /etc/supervisor/conf.d/websocket.conf
supervisorctl reread
supervisorctl update

supervisorctl start worker:*
supervisorctl start websocket

echo "* * * * * cd /home/vito/vito && php artisan schedule:run >> /dev/null 2>&1" | sudo -u vito crontab -

echo "🎉 Congratulations!"
echo "✅ You can access Vito at: ${VITO_APP_URL}"
echo "✅ SSH User: vito"
echo "✅ SSH Password: ${V_PASSWORD}"
echo "✅ Admin Email: ${V_ADMIN_EMAIL}"
echo "✅ Admin Password: ${V_ADMIN_PASSWORD}"
