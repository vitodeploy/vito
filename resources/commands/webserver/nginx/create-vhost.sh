if ! rm -rf __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! mkdir __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo chown -R 755 __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! echo '' | sudo tee /etc/nginx/conf.d/__domain___redirects; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! echo '__vhost__' | sudo tee /etc/nginx/sites-available/__domain__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ln -s /etc/nginx/sites-available/__domain__ /etc/nginx/sites-enabled/; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service nginx restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
