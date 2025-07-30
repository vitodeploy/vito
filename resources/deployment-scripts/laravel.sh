cd $SITE_PATH

php artisan down

git pull origin $BRANCH

composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force

php artisan optimize:clear
php artisan optimize

sudo service supervisor restart

php artisan up

echo "✅ Deployment completed successfully!"
