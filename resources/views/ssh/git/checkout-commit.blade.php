if ! cd {!! $path !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git cat-file -e {!! $commit !!}^{commit} 2>/dev/null && ! git fetch origin {!! $commit !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git checkout -f {!! $commit !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
