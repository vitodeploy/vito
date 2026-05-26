if ! cd "{{ $path }}"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
