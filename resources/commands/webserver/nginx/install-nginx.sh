sudo DEBIAN_FRONTEND=noninteractive apt install nginx -y

if ! echo '__config__' | sudo tee /etc/nginx/nginx.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo service nginx start

