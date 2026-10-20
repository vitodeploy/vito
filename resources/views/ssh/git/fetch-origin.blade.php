if ! cd {!! $path !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git fetch origin {!! $commit ?? '' !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
