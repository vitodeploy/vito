if [ -f ~/.ssh/config ]; then
    if ! awk -v alias_name='{{ $host }}-{{ $key }}' '
        /^Host[[:space:]]/ { skip = ($2 == alias_name && NF == 2) ? 1 : 0 }
        !skip { print }
    ' ~/.ssh/config > ~/.ssh/config.vito.tmp; then
        rm -f ~/.ssh/config.vito.tmp
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    mv ~/.ssh/config.vito.tmp ~/.ssh/config
fi

echo "Host {{ $host }}-{{ $key }}
        Hostname {{ $host }}
        Port {{ $port }}
        IdentityFile=~/.ssh/{{ $key }}" >> ~/.ssh/config

chmod 600 ~/.ssh/config

ssh-keyscan -T 5 -p {{ $port }} -H {{ $host }} >> ~/.ssh/known_hosts

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
