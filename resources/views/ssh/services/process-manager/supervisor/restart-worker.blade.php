if ! sudo supervisorctl reread; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo supervisorctl update {{ $id }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo supervisorctl restart {{ $id }}:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
