if ! echo "__content__" | tee __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
