export DEBIAN_FRONTEND=noninteractive

if ! rm -rf __path__; then
    echo 'VITO_SSH_ERROR'
    exit 1
fi

if ! mkdir __path__; then
    echo 'VITO_SSH_ERROR'
    exit 1
fi

if ! chmod -R 755 __path__; then
    echo 'VITO_SSH_ERROR'
    exit 1
fi
