if ! sudo mkdir -p "$(dirname {{ $logFile }})"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo touch {{ $logFile }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo chown {{ $user }}:{{ $user }} {{ $logFile }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl reread; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl update; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl start {{ $id }}:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
