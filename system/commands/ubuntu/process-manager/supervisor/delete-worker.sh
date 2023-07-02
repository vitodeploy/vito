if ! sudo supervisorctl stop __id__:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo rm -rf ~/.logs/workers/__id__.log; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo rm -rf /etc/supervisor/conf.d/__id__.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl reread; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl update; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

