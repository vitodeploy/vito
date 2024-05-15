echo "Updating Vito..."

cd /home/vito/vito

echo "Pulling changes..."
git fetch --all

echo "Checking out the latest tag..."
NEW_RELEASE=$(git tag -l --merged 1.x --sort=-v:refname | head -n 1)
git checkout "$NEW_RELEASE"

echo "Installing composer dependencies..."
composer install --no-dev

echo "Running migrations..."
php artisan migrate --force

echo "Optimizing..."
php artisan optimize:clear
php artisan optimize

echo "Restarting workers..."
sudo supervisorctl restart worker:*

bash scripts/post-update.sh

echo "Vito updated successfully to $NEW_RELEASE! 🎉"
