if ! echo '__redirects__' | sudo tee /etc/nginx/conf.d/__domain___redirects; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service nginx restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
