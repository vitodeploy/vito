if [ -d /run/systemd/system ] && sudo bash -c 'command -v ip' >/dev/null 2>&1; then
    echo 'VITO_NETWORK_BACKEND=systemd'
else
    echo 'VITO_NETWORK_BACKEND=none'
fi
