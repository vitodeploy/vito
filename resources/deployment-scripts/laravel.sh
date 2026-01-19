git pull origin $BRANCH

composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force

php artisan optimize:clear
php artisan optimize

npm ci
npm run build

echo "✅ Deployment completed successfully!"
