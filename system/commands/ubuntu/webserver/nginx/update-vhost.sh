if ! echo '__vhost__' | sudo tee /etc/nginx/sites-available/__domain__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service nginx restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
