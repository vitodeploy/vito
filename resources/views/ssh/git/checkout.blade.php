if ! cd {{ $path }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git checkout -f {{ $branch }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
