@if (! empty($usesToken))
rm -rf {!! $path !!}

if ! git -c credential.helper='!f() { test "$1" = get && printf "username=x-access-token\npassword=%s\n\n" "$GIT_HTTP_TOKEN"; }; f' clone -b {!! $branch !!} {!! $repo !!} {!! $path !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! cd {!! $path !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git config credential.helper '!f() { test "$1" = get && printf "username=x-access-token\npassword=%s\n\n" "$GIT_HTTP_TOKEN"; }; f'; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git config core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find {!! $path !!} -type d -exec chmod 755 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find {!! $path !!} -type f -exec chmod 644 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@else
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

rm -rf {!! $path !!}

if ! git clone -b {!! $branch !!} {!! $repo !!} {!! $path !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find {!! $path !!} -type d -exec chmod 755 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find {!! $path !!} -type f -exec chmod 644 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! cd {!! $path !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git config core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif
