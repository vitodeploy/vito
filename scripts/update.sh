#!/bin/bash

echo "Updating Vito..."

cd /home/vito/vito

echo "Discarding any possible local changes..."
git reset --hard HEAD
git clean -fd

echo "Pulling changes..."
git fetch --all

INCLUDE_PATTERN='^3\.[0-9]+\.[0-9]+$' # stable only

if [[ "$1" == "--alpha" ]]; then
  INCLUDE_PATTERN='^3\.[0-9]+\.[0-9]+(-alpha-[0-9]+|-beta-[0-9]+|-rc-[0-9]+)?$'
elif [[ "$1" == "--beta" ]]; then
  INCLUDE_PATTERN='^3\.[0-9]+\.[0-9]+(-beta-[0-9]+|-rc-[0-9]+)?$'
fi

# Filter and sort matching tags
MATCHING_TAGS=$(git tag | grep -E "$INCLUDE_PATTERN" | sort -V)

# Get the latest tag from the list
NEW_RELEASE=$(echo "$MATCHING_TAGS" | tail -n 1)

if [[ -z "$NEW_RELEASE" ]]; then
  echo "❌ No matching tag found."
  exit 1
fi

echo "Switching to tag: $NEW_RELEASE"
git checkout "$NEW_RELEASE"
git pull origin "$NEW_RELEASE"

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

echo "✅ Vito updated successfully to $NEW_RELEASE! 🎉"
