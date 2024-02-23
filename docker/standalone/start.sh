#!/bin/bash

INIT_FLAG="/var/www/html/storage/.INIT_ENV"
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-"password"}
NAME=${NAME:-"vito"}
EMAIL=${EMAIL:-"vito@example.com"}
PASSWORD=${PASSWORD:-"password"}

# Check if the flag file does not exist, indicating a first run
if [ ! -f "$INIT_FLAG" ]; then
    echo "First run of the container. Initializing MySQL..."

    # Start MySQL temporarily
    service mysql start

    # Wait for MySQL to start up completely (may need to adjust the sleep duration)
    sleep 3

    # Create Database
    mysql -u root -p`echo password` -e "CREATE DATABASE IF NOT EXISTS vito CHARACTER SET utf8 COLLATE utf8_general_ci;"

    # Change Password
    mysql -u root -p`echo password` -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$MYSQL_ROOT_PASSWORD'; FLUSH PRIVILEGES;"

    cp /var/www/html/.env.docker /var/www/html/.env

    # Modify /var/www/html/.env and set DB_PASSWORD=password to DB_PASSWORD=MYSQL_ROOT_PASSWORD
    sed -i "s/DB_PASSWORD=password/DB_PASSWORD=$MYSQL_ROOT_PASSWORD/g" /var/www/html/.env

    php /var/www/html/artisan key:generate

    php /var/www/html/artisan migrate --force

    php /var/www/html/artisan user:create "$NAME" "$EMAIL" "$PASSWORD"

    openssl genpkey -algorithm RSA -out /var/www/html/storage/ssh-private.pem
    chmod 600 /var/www/html/storage/ssh-private.pem
    ssh-keygen -y -f /var/www/html/storage/ssh-private.pem > /var/www/html/storage/ssh-public.key

    # Create the flag file to indicate completion of initialization tasks
    touch /var/www/html/storage/"$INIT_FLAG"
fi

service mysql start

service php8.1-fpm start

service nginx start

php /var/www/html/artisan migrate --force
php /var/www/html/artisan config:clear
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:clear
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:clear
php /var/www/html/artisan view:cache
php /var/www/html/artisan icons:cache

echo "Vito is running! 🚀"

tail -f /dev/null
