if ! cd {{ $path }}; then
echo 'VITO_SSH_ERROR' && exit 1
fi

if [[ {{ $branch }} =~ ^pr-[0-9]+$ ]] && ! git rev-parse --verify {{ $branch }}; then
if ! git fetch origin "pull/{{ substr($branch, 3) }}/head:{{ $branch }}"; then
echo 'VITO_SSH_ERROR' && exit 1
fi
fi

if ! git checkout -f {{ $branch }}; then
echo 'VITO_SSH_ERROR' && exit 1
fi
