if ! bash -c 'set -o pipefail; GZIP=$(command -v pigz || echo gzip); sudo DEBIAN_FRONTEND=noninteractive mysqldump --single-transaction --quick --no-tablespaces -u root "$1" | $GZIP > "$2"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
