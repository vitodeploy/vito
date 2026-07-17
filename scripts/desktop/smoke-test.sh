#!/usr/bin/env bash
#
# Boots the assembled desktop app distribution (desktop-build/app) with the
# given PHP binary the same way the Electron shell does: verifies required
# extensions, runs migrations, caches config/routes/views, then serves HTTP
# and asserts the health endpoint responds.
#
# Usage: scripts/desktop/smoke-test.sh <php-binary>
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
APP="$ROOT/desktop-build/app"
PHP_BIN="${1:?usage: smoke-test.sh <php-binary>}"

if [ ! -f "$APP/artisan" ]; then
  echo "desktop-build/app is missing. Run scripts/desktop/build-app.sh first." >&2
  exit 1
fi

to_php_path() {
  if command -v cygpath >/dev/null 2>&1; then
    cygpath -m "$1"
  else
    echo "$1"
  fi
}

IS_WINDOWS=false
case "$(uname -s)" in
  MINGW* | MSYS* | CYGWIN*) IS_WINDOWS=true ;;
esac

BIN_DIR="$(cd "$(dirname "$PHP_BIN")" && pwd)"
PHP=("$PHP_BIN")

if [ -f "$BIN_DIR/cacert.pem" ]; then
  CA_PATH="$(to_php_path "$BIN_DIR/cacert.pem")"
  PHP+=(-d "curl.cainfo=$CA_PATH" -d "openssl.cafile=$CA_PATH")
fi

if [ "$IS_WINDOWS" = "true" ] && [ -d "$BIN_DIR/ext" ]; then
  PHP+=(-d "extension_dir=$(to_php_path "$BIN_DIR/ext")")
fi

"${PHP[@]}" -v

REQUIRED_EXTENSIONS=(ctype curl dom fileinfo filter ftp iconv intl mbstring openssl pdo_sqlite phar session simplexml sockets sqlite3 tokenizer xml xmlreader xmlwriter zip zlib)
if [ "$IS_WINDOWS" != "true" ]; then
  REQUIRED_EXTENSIONS+=(pcntl posix)
fi

LOADED="$("${PHP[@]}" -m)"
for extension in "${REQUIRED_EXTENSIONS[@]}"; do
  if ! grep -qix "$extension" <<< "$LOADED"; then
    echo "Bundled PHP is missing required extension: $extension" >&2
    exit 1
  fi
done

DATA="$(mktemp -d)"
trap 'rm -rf "$DATA"' EXIT

mkdir -p \
  "$DATA/storage/app/public" \
  "$DATA/storage/framework/cache/data" \
  "$DATA/storage/framework/sessions" \
  "$DATA/storage/framework/views" \
  "$DATA/storage/logs" \
  "$DATA/runtime/cache"

touch "$DATA/storage/database.sqlite"

PHP_DATA="$(to_php_path "$DATA")"

export VITO_DESKTOP=true
export VITO_DATA_PATH="$PHP_DATA"
export VITO_ENV_PATH="$PHP_DATA/runtime"
export VITO_STORAGE_PATH="$PHP_DATA/storage"
export APP_ENV=production
export APP_DEBUG=false
export APP_KEY="base64:$("${PHP[@]}" -r 'echo base64_encode(random_bytes(32));')"
export APP_URL="http://127.0.0.1:18211"
export APP_SERVICES_CACHE="$PHP_DATA/runtime/cache/services.php"
export APP_PACKAGES_CACHE="$PHP_DATA/runtime/cache/packages.php"
export APP_CONFIG_CACHE="$PHP_DATA/runtime/cache/config.php"
export APP_ROUTES_CACHE="$PHP_DATA/runtime/cache/routes.php"
export APP_EVENTS_CACHE="$PHP_DATA/runtime/cache/events.php"
export VIEW_COMPILED_PATH="$PHP_DATA/storage/framework/views"
export DB_CONNECTION=sqlite
export DB_DATABASE=database.sqlite
export QUEUE_CONNECTION=database
export CACHE_DRIVER=file
export SESSION_DRIVER=file
export FILESYSTEM_DISK=local
export MAIL_MAILER=log
export WS_HOST=127.0.0.1
export WS_PORT=18212
export WS_BROADCAST_SECRET=smoke-test-secret

touch "$DATA/runtime/.env"

cd "$APP"

"${PHP[@]}" artisan --version
"${PHP[@]}" artisan migrate --force
"${PHP[@]}" artisan optimize

ROUTER="$(to_php_path "$APP/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php")"
(cd "$APP/public" && exec "${PHP[@]}" -S 127.0.0.1:18211 "$ROUTER") &
SERVER_PID=$!
trap 'kill "$SERVER_PID" 2>/dev/null || true; rm -rf "$DATA"' EXIT

HEALTHY=false
for _ in $(seq 1 30); do
  if curl -fsS "http://127.0.0.1:18211/api/health" | grep -q '"success":true'; then
    HEALTHY=true
    break
  fi
  sleep 1
done

if [ "$HEALTHY" != "true" ]; then
  echo "Desktop app HTTP health check failed" >&2
  exit 1
fi

curl -fsS "http://127.0.0.1:18211/desktop/login" >/dev/null

echo "Desktop app smoke test passed"
