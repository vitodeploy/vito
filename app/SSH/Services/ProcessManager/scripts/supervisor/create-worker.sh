if ! sudo mkdir -p "$(dirname __log_file__)"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo touch __log_file__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo chown __user__:__user__ __log_file__; then
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
