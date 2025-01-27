echo "Host {{ $host }}-{{ $key }}
        Hostname {{ $host }}
        IdentityFile=~/.ssh/{{ $key }}" >> ~/.ssh/config

chmod 600 ~/.ssh/config

ssh-keyscan -H {{ $host }} >> ~/.ssh/known_hosts

rm -rf {{ $path }}

if ! git config --global core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git clone -b {{ $branch }} {{ $repo }} {{ $path }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find {{ $path }} -type d -exec chmod 755 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find {{ $path }} -type f -exec chmod 644 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! cd {{ $path }} && git config core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
