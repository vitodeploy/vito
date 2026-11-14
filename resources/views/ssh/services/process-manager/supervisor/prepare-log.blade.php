if ! sudo install -d -o root -g {!! escapeshellarg($user) !!} -m 750 {!! escapeshellarg(dirname($logFile)) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

@if ($previousLogFile ?? null)
sudo -u {!! escapeshellarg($user) !!} tail -n 500 -- {!! escapeshellarg($previousLogFile) !!} 2>/dev/null | sudo tee {!! escapeshellarg($logFile) !!} > /dev/null || true

@endif
if ! sudo touch {!! escapeshellarg($logFile) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
