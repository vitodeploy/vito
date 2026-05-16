if ! sudo ln -sfn {!! escapeshellarg('/etc/nginx/sites-available/'.$domain) !!} {!! escapeshellarg('/etc/nginx/sites-enabled/'.$domain) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service nginx reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
