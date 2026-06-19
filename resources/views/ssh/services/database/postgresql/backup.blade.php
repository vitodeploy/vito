if ! bash -c 'set -o pipefail; GZIP=$(command -v pigz || echo gzip); sudo -u postgres pg_dump -d "$1" | $GZIP > "$2"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
