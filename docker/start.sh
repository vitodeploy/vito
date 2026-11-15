#!/bin/bash

INIT_FLAG="/var/www/html/storage/.INIT_ENV"
NAME=${NAME:-"vito"}
EMAIL=${EMAIL:-"vito@vitodeploy.com"}
PASSWORD=${PASSWORD:-"password"}

check_length() {
  local key=$1
  if [ ${#key} -ne 32 ]; then
    echo "Invalid APP_KEY"
    exit 1
  fi
}

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY is not set"
  exit 1
fi

if [[ $APP_KEY == base64:* ]]; then
  decoded_key=$(echo "${APP_KEY:7}" | base64 --decode 2>/dev/null)

  if [ $? -ne 0 ]; then
    echo "Invalid APP_KEY base64 encoding"
    exit 1
  fi

  check_length "$decoded_key"
else
  check_length "$APP_KEY"
fi

if [ ! -f "$INIT_FLAG" ]; then
  echo "Initializing..."

  openssl genpkey -algorithm RSA -out /var/www/html/storage/ssh-private.pem
  chmod 600 /var/www/html/storage/ssh-private.pem
  ssh-keygen -y -f /var/www/html/storage/ssh-private.pem >/var/www/html/storage/ssh-public.key

  touch /var/www/html/storage/database.sqlite

  touch "$INIT_FLAG"
fi

chown -R www-data:www-data /var/www/html &&
  chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache
service php8.4-fpm start

service redis-server start
service nginx start

php /var/www/html/artisan migrate --force
php /var/www/html/artisan optimize:clear
php /var/www/html/artisan optimize

printf '%s' "$PASSWORD" | php /var/www/html/artisan user:create "$NAME" "$EMAIL" --password-stdin

cron

echo "Vito is running! 🚀"

/usr/bin/supervisord
