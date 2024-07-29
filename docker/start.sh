#!/bin/bash

INIT_FLAG="/var/www/html/storage/.INIT_ENV"
NAME=${NAME:-"vito"}
EMAIL=${EMAIL:-"vito@vitodeploy.com"}
PASSWORD=${PASSWORD:-"password"}

# Function to check if a string is 32 characters long
check_length() {
    local key=$1
    if [ ${#key} -ne 32 ]; then
        echo "Invalid APP_KEY"
        exit 1
    fi
}

# Check if APP_KEY is set
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set"
    exit 1
fi

# Check if APP_KEY starts with 'base64:'
if [[ $APP_KEY == base64:* ]]; then
    # Remove 'base64:' prefix and decode the base64 string
    decoded_key=$(echo "${APP_KEY:7}" | base64 --decode 2>/dev/null)

    # Check if decoding was successful
    if [ $? -ne 0 ]; then
        echo "Invalid APP_KEY base64 encoding"
        exit 1
    fi

    # Check the length of the decoded key
    check_length "$decoded_key"
else
    # Check the length of the raw APP_KEY
    check_length "$APP_KEY"
fi

# check if the flag file does not exist, indicating a first run
if [ ! -f "$INIT_FLAG" ]; then
    echo "Initializing..."

    # generate SSH keys
    openssl genpkey -algorithm RSA -out /var/www/html/storage/ssh-private.pem
    chmod 600 /var/www/html/storage/ssh-private.pem
    ssh-keygen -y -f /var/www/html/storage/ssh-private.pem > /var/www/html/storage/ssh-public.key

    # create sqlite database
    touch /var/www/html/storage/database.sqlite

    # create the flag file to indicate completion of initialization tasks
    touch "$INIT_FLAG"
fi

chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache
service php8.2-fpm start

service nginx start

php /var/www/html/artisan migrate --force
php /var/www/html/artisan config:clear
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:clear
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:clear
php /var/www/html/artisan view:cache

php /var/www/html/artisan user:create "$NAME" "$EMAIL" "$PASSWORD"

cron

echo "Vito is running! 🚀"

/usr/bin/supervisord
