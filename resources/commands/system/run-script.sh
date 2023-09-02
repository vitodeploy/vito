if ! cd __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! __script__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
