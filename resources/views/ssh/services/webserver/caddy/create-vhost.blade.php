if ! sudo ln -sfn /etc/caddy/sites-available/{!! escapeshellarg($domain) !!} /etc/caddy/sites-enabled/{!! escapeshellarg($domain) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service caddy reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
