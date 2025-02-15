if ! sudo mariadb -e "CREATE USER IF NOT EXISTS '{{ $username }}'@'{{ $host }}' IDENTIFIED BY '{{ $password }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mariadb -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
