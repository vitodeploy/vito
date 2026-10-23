if ! cd -- {!! $path === '~' || str_starts_with($path, '~/') ? '"$HOME"'.escapeshellarg(substr($path, 1)) : escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

{!! $script !!}
