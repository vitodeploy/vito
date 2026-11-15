mkdir -p ~/.ssh
chmod 700 ~/.ssh
exec 9> ~/.ssh/.vito-authorized-keys.lock
flock 9
if ! printf '%s\n' {!! escapeshellarg($key) !!} >> ~/.ssh/authorized_keys; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
chmod 600 ~/.ssh/authorized_keys
