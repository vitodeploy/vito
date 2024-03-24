if ! sudo -u postgres pg_dump -d __database__ -f /var/lib/postgresql/__file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mv /var/lib/postgresql/__file__.sql /home/vito/__file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo chown vito:vito /home/vito/__file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! DEBIAN_FRONTEND=noninteractive zip __file__.zip __file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! rm __file__.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
