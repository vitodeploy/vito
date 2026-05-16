if ! sudo ln -sfn /etc/nginx/sites-available/{!! escapeshellarg($domain) !!} /etc/nginx/sites-enabled/{!! escapeshellarg($domain) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service nginx reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
