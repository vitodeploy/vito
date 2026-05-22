if [ ! -d {!! $path !!}/.git ]; then
    exit 0
fi

if ! cd {!! $path !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git remote set-url origin {!! $newRepoUrl !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

@if (! empty($usesToken))
if ! git config credential.helper '!f() { test "$1" = get && printf "username=x-access-token\npassword=%s\n\n" "$GIT_HTTP_TOKEN"; }; f'; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@else
git config --unset credential.helper || true
@endif
