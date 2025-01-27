if ! sudo ufw {{ $type }} from {{ $source }}{{ $mask }} to any proto {{ $protocol }} port {{ $port }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service ufw restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
