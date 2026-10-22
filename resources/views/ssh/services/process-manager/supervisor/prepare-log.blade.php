if ! sudo install -d -o root -g {!! escapeshellarg($user) !!} -m 750 {!! escapeshellarg(dirname($logFile)) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo touch {!! escapeshellarg($logFile) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
