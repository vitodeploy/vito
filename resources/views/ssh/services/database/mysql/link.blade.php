if ! sudo mysql -e "GRANT ALL PRIVILEGES ON {{ $database }}.* TO '{{ $username }}'@'{{ $host }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Linking to {{ $database }} finished"
