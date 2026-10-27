if sudo test -f /etc/letsencrypt/renewal/{{ $ssl->id }}.conf; then
    if ! sudo certbot delete --cert-name {{ $ssl->id }} --noninteractive; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi
if ! sudo rm -rf /etc/ssl/vito/{{ $ssl->id }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
