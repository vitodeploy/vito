php artisan down

git pull

composer install --no-dev

php artisan migrate --force

php artisan config:clear
php artisan cache:clear
php artisan view:clear

php artisan config:cache
php artisan icons:cache

sudo supervisorctl restart worker:*

php artisan up
