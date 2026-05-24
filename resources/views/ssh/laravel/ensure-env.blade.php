if [ -f '{{ $envPath }}' ]; then
    exit 0
fi

if [ -f '{{ $examplePath }}' ]; then
    if ! cp -- '{{ $examplePath }}' '{{ $envPath }}'; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
else
    if ! touch -- '{{ $envPath }}'; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi

if ! chmod 640 -- '{{ $envPath }}'; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
