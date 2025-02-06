if ! echo '{!! $redirects !!}' | sudo tee /etc/nginx/conf.d/{{ $domain }}_redirects; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service nginx restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
