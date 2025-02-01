if ! sudo certbot certonly --force-renewal --nginx --noninteractive --agree-tos --cert-name {{ $name }} -m {{ $email }} {{ $domains }} --verbose; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
