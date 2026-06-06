if ! sudo supervisorctl update; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl restart all; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "All workers restarted successfully."
