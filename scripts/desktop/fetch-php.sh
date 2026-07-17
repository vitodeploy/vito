#!/usr/bin/env bash
#
# Provisions the portable PHP runtime bundled with the desktop app into
# desktop/resources/bin/<platform>/<arch>/.
#
# macOS/Linux: builds a static PHP CLI binary with static-php-cli (spc) using
# the exact extension set Vito needs. CI caches the output directory, so the
# build only runs when this script changes.
# Windows: downloads the official php.net NTS build and generates a php.ini
# enabling the required extension DLLs.
#
# Usage: scripts/desktop/fetch-php.sh <darwin|linux|win32> <arm64|x64>
set -euo pipefail

PLATFORM="${1:?usage: fetch-php.sh <darwin|linux|win32> <arm64|x64>}"
ARCH="${2:?usage: fetch-php.sh <darwin|linux|win32> <arm64|x64>}"

PHP_SERIES="8.4"
SPC_VERSION="2.8.5"
COMMON_EXTENSIONS="bcmath,ctype,curl,dom,fileinfo,filter,ftp,gmp,iconv,intl,mbstring,mbregex,opcache,openssl,pdo,pdo_sqlite,phar,session,simplexml,sockets,sodium,sqlite3,tokenizer,xml,xmlreader,xmlwriter,zip,zlib"
UNIX_EXTENSIONS="$COMMON_EXTENSIONS,pcntl,posix"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TARGET="$ROOT/desktop/resources/bin/$PLATFORM/$ARCH"
mkdir -p "$TARGET"

PHP_BINARY="$TARGET/php"
if [ "$PLATFORM" = "win32" ]; then
  PHP_BINARY="$TARGET/php.exe"
fi

if [ ! -f "$TARGET/cacert.pem" ]; then
  curl -fsSL --retry 3 https://curl.se/ca/cacert.pem -o "$TARGET/cacert.pem"
fi

if [ -f "$PHP_BINARY" ]; then
  echo "PHP runtime already present at $PHP_BINARY"
  "$PHP_BINARY" -v
  exit 0
fi

fetch_windows() {
  local zip="$TARGET/php-windows.zip"

  curl -fsSL --retry 3 \
    "https://windows.php.net/downloads/releases/latest/php-${PHP_SERIES}-nts-Win32-vs17-x64-latest.zip" \
    -o "$zip"
  unzip -qo "$zip" -d "$TARGET"
  rm -f "$zip"

  cat > "$TARGET/php.ini" <<'INI'
extension_dir = "ext"
extension = curl
extension = fileinfo
extension = ftp
extension = gmp
extension = intl
extension = mbstring
extension = openssl
extension = pdo_sqlite
extension = sockets
extension = sodium
extension = sqlite3
extension = zip
zend_extension = opcache
memory_limit = 512M
INI

  local system32="/c/Windows/System32"
  for dll in vcruntime140.dll vcruntime140_1.dll msvcp140.dll; do
    if [ -f "$system32/$dll" ] && [ ! -f "$TARGET/$dll" ]; then
      cp "$system32/$dll" "$TARGET/"
    fi
  done
}

fetch_unix() {
  local spc_platform
  local spc_arch

  case "$PLATFORM" in
    darwin) spc_platform="macos" ;;
    linux) spc_platform="linux" ;;
    *) echo "Unsupported platform: $PLATFORM" >&2; exit 1 ;;
  esac

  case "$ARCH" in
    arm64) spc_arch="aarch64" ;;
    x64) spc_arch="x86_64" ;;
    *) echo "Unsupported arch: $ARCH" >&2; exit 1 ;;
  esac

  local workdir
  workdir="$(mktemp -d)"

  curl -fsSL --retry 3 \
    "https://github.com/crazywhalecc/static-php-cli/releases/download/${SPC_VERSION}/spc-${spc_platform}-${spc_arch}.tar.gz" \
    -o "$workdir/spc.tar.gz"
  tar -xzf "$workdir/spc.tar.gz" -C "$workdir"
  chmod +x "$workdir/spc"

  (
    cd "$workdir"
    ./spc doctor --auto-fix
    ./spc download --with-php="$PHP_SERIES" --for-extensions="$UNIX_EXTENSIONS" --prefer-pre-built
    ./spc build --build-cli "$UNIX_EXTENSIONS"
  )

  cp "$workdir/buildroot/bin/php" "$PHP_BINARY"
  chmod +x "$PHP_BINARY"
  rm -rf "$workdir"
}

if [ "$PLATFORM" = "win32" ]; then
  fetch_windows
else
  fetch_unix
fi

"$PHP_BINARY" -v
echo "PHP runtime installed at $PHP_BINARY"
