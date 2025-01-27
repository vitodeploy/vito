if ! sudo -u postgres pg_dump -d {{ $database }} -f /var/lib/postgresql/{{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mv /var/lib/postgresql/{{ $file }}.sql /home/vito/{{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo chown vito:vito /home/vito/{{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! DEBIAN_FRONTEND=noninteractive zip {{ $file }}.zip {{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! rm {{ $file }}.sql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
