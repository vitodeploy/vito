sudo DEBIAN_FRONTEND=noninteractive apt-get install nginx -y
if ! echo '__config__' | sudo tee /etc/nginx/nginx.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
sudo service nginx start

# install certbot
sudo DEBIAN_FRONTEND=noninteractive apt-get install certbot python3-certbot-nginx -y
