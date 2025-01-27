if ! sudo ln -s /etc/nginx/sites-available/{{ $domain }} /etc/nginx/sites-enabled/; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service nginx restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
