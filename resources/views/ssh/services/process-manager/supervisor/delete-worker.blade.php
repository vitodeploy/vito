@if (! ($component ?? false))
if ! sudo supervisorctl stop {{ $id }}:*; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

@if ($logFile ?? null)
if ! sudo sh -c {!! escapeshellarg('rm -f -- '.escapeshellarg($logFile).' '.escapeshellarg($logFile).'.*') !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

if ! sudo rm -rf /etc/supervisor/conf.d/{{ $id }}.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl reread; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl update; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
