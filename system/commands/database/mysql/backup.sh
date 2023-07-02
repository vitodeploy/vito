if ! sudo DEBIAN_FRONTEND=noninteractive mysqldump -u root __database__ > __file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! DEBIAN_FRONTEND=noninteractive zip __file__.zip __file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! rm __file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
