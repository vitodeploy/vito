if ! (
    flock -w 60 9 || exit 1
    if [ -f ~/.ssh/config ]; then
        if ! awk -v alias_name='{{ $host }}-{{ $key }}' '
            /^Host[[:space:]]/ { skip = ($2 == alias_name && NF == 2) ? 1 : 0 }
            !skip { print }
        ' ~/.ssh/config > ~/.ssh/config.vito.tmp; then
            rm -f ~/.ssh/config.vito.tmp
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        mv ~/.ssh/config.vito.tmp ~/.ssh/config || exit 1
    fi

    echo "Host {{ $host }}-{{ $key }}
        Hostname {{ $host }}
        Port {{ $port }}
        IdentityFile=~/.ssh/{{ $key }}" >> ~/.ssh/config || exit 1

    chmod 600 ~/.ssh/config
) 9>~/.ssh/config.vito.lock; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! ssh-keygen -F '{{ (int) $port === 22 ? $host : '['.$host.']:'.$port }}' > /dev/null 2>&1; then
    host_keys=$(ssh-keyscan -T 5 -p {{ $port }} -H {{ $host }} 2> /dev/null)
    if [ -z "$host_keys" ]; then
        echo 'VITO_SSH_ERROR: could not read the SSH host key of {{ $host }}:{{ $port }}' && exit 1
    fi
    printf '%s\n' "$host_keys" >> ~/.ssh/known_hosts
fi
