if ! sudo ln -sfn {!! escapeshellarg('/etc/caddy/sites-available/'.$domain) !!} {!! escapeshellarg('/etc/caddy/sites-enabled/'.$domain) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service caddy reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
