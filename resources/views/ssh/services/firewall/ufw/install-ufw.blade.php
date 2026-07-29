if [ -f /proc/net/if_inet6 ] && [ -f /etc/default/ufw ] && ! grep -q '^IPV6=yes' /etc/default/ufw; then
    if ! sudo sed -i '/^IPV6=/d' /etc/default/ufw; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! echo 'IPV6=yes' | sudo tee -a /etc/default/ufw > /dev/null; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi

if ! sudo ufw default deny incoming; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw default allow outgoing; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw allow proto tcp to any port {{ $sshPort }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw allow proto tcp to any port 80; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw allow proto tcp to any port 443; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw --force enable; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
