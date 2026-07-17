#!/usr/bin/env bash
#
# Assembles the Laravel application distribution consumed by the desktop
# Electron shell into desktop-build/app.
#
# Prerequisites (run from the repository root):
#   composer install --no-dev --optimize-autoloader
#   npm run build:desktop
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DIST="$ROOT/desktop-build/app"

if [ ! -f "$ROOT/vendor/autoload.php" ]; then
  echo "vendor/autoload.php is missing. Run: composer install --no-dev --optimize-autoloader" >&2
  exit 1
fi

if [ -f "$ROOT/vendor/bin/phpunit" ]; then
  echo "Warning: vendor contains dev dependencies. Run composer install with --no-dev for release builds." >&2
fi

if [ ! -f "$ROOT/public/build-desktop/manifest.json" ]; then
  echo "public/build-desktop/manifest.json is missing. Run: npm run build:desktop" >&2
  exit 1
fi

rm -rf "$ROOT/desktop-build"
mkdir -p "$DIST"

INCLUDES=(
  app
  bootstrap
  config
  database
  lang
  public
  resources/deployment-scripts
  resources/views
  vendor
  artisan
  composer.json
  composer.lock
)

tar -cf - \
  -C "$ROOT" \
  --exclude='public/build' \
  --exclude='public/hot' \
  --exclude='public/storage' \
  --exclude='bootstrap/cache/*.php' \
  --exclude='.DS_Store' \
  "${INCLUDES[@]}" | tar -xf - -C "$DIST"

for file in \
  "$DIST/artisan" \
  "$DIST/vendor/autoload.php" \
  "$DIST/public/index.php" \
  "$DIST/public/build-desktop/manifest.json" \
  "$DIST/resources/views/ssh/services/webserver/nginx/vhost.mustache"; do
  if [ ! -f "$file" ]; then
    echo "Desktop app distribution is incomplete: missing $file" >&2
    exit 1
  fi
done

echo "Desktop app distribution assembled at $DIST"
