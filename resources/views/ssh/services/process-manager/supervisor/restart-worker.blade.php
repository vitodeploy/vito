if ! sudo supervisorctl restart {{ $id }}:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
