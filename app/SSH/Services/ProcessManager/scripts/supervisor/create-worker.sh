mkdir -p ~/.logs

mkdir -p ~/.logs/workers

touch ~/.logs/workers/__id__.log

if ! echo '__config__' | sudo tee /etc/supervisor/conf.d/__id__.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl reread; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl update; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl start __id__:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
