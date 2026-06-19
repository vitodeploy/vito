if ! bash -c 'set -o pipefail; gunzip -c "$2" | sudo DEBIAN_FRONTEND=noninteractive mariadb -u root "$1"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

rm -f {!! escapeshellarg($path) !!}
