#!/bin/bash

cd /home/vito/vito

php artisan down

git pull

composer install --no-dev

php artisan migrate --force

php artisan config:clear
php artisan cache:clear
php artisan view:clear

php artisan config:cache

sudo supervisorctl restart worker:*

php artisan up
