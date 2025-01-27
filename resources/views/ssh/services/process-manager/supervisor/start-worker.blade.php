if ! sudo supervisorctl start {{ $id }}:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
