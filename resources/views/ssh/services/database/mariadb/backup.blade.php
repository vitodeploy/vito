if ! sudo DEBIAN_FRONTEND=noninteractive mysqldump -u root {{ $database }} > {{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! DEBIAN_FRONTEND=noninteractive zip {{ $file }}.zip {{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! rm {{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
