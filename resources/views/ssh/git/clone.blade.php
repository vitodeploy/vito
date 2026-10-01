@if (! empty($usesToken))
rm -rf {!! $path !!}

if ! git config --global core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

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
@include('ssh.git.ssh-host')

rm -rf {!! $path !!}

if ! git config --global core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

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
