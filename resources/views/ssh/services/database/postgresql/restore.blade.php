if ! bash -c 'set -o pipefail; gunzip -c "$2" | sudo -u postgres psql -v ON_ERROR_STOP=1 -d "$1"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

rm -f {!! escapeshellarg($path) !!}
