if ! wget {{ $url }} -O {{ $path }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
