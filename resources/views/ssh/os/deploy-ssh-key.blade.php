mkdir -p ~/.ssh
chmod 700 ~/.ssh
if ! echo '{!! $key !!}' >> ~/.ssh/authorized_keys; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
chmod 600 ~/.ssh/authorized_keys
