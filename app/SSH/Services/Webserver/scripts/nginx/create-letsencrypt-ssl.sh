if ! sudo certbot certonly --force-renewal --nginx --noninteractive --agree-tos --cert-name __domain__ -m __email__ -d __domain__ --verbose; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
