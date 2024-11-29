echo "Updating Vito..."

cd /home/vito/vito

echo "Pulling changes..."
git fetch --all

echo "Checking out the latest tag..."
NEW_RELEASE=$(git tag -l "2.*" --sort=-v:refname | head -n 1)
git checkout "$NEW_RELEASE"

git pull origin "$NEW_RELEASE"

echo "Installing composer dependencies..."
composer install --no-dev

echo "Running migrations..."
php artisan migrate --force

echo "Optimizing..."
php artisan optimize:clear
php artisan icons:clear
php artisan filament:optimize-clear
php artisan filament:clear-cached-components
php artisan optimize
php artisan icons:cache
php artisan filament:optimize
php artisan filament:cache-components

echo "Restarting workers..."
sudo supervisorctl restart worker:*

bash scripts/post-update.sh

echo "Vito updated successfully to $NEW_RELEASE! 🎉"
