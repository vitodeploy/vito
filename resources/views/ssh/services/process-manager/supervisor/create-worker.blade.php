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

if ! output=$(sudo supervisorctl start {{ $id }}:* 2>&1); then
    echo "$output"
    echo 'VITO_SSH_ERROR' && exit 1
fi
echo "$output"
if echo "$output" | grep ': ERROR' | grep -qvE 'already started|not running'; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
