if ! sudo supervisorctl stop __id__:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
