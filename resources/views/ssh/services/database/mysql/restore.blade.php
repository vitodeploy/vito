if ! bash -c 'set -o pipefail; gunzip -c {{ $path }} | sudo DEBIAN_FRONTEND=noninteractive mysql -u root {{ $database }}'; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

rm -f {{ $path }}
