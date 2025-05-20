if ! sudo sed -i 's/php{{ $oldVersion }}/php{{ $newVersion }}/g' /etc/caddy/sites-available/{{ $domain }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service caddy restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "PHP Version Changed to {{ $newVersion }}"
