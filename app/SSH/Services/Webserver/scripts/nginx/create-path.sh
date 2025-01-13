export DEBIAN_FRONTEND=noninteractive

if ! rm -rf __path__; then
    echo 'VITO_SSH_ERROR_1'
    exit 1
fi

if ! mkdir __path__; then
    echo 'VITO_SSH_ERROR_2'
    exit 1
fi

if ! chmod -R 755 __path__; then
    echo 'VITO_SSH_ERROR_3'
    exit 1
fi
