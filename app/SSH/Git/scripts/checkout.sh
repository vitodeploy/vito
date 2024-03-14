if ! cd __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git checkout -f __branch__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
