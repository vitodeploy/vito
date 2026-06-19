if ! bash -c 'set -o pipefail; GZIP=$(command -v pigz || echo gzip); sudo -u postgres pg_dump -d {{ $database }} | $GZIP > {{ $path }}'; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
