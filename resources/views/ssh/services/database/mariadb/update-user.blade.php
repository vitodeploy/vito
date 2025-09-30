@if ($newPassword)
if ! sudo mariadb -e "ALTER USER '{{ $username }}'@'{{ $host }}' IDENTIFIED BY '{{ $newPassword }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

@if ($newHost && $newHost != $host)
if ! sudo mariadb -e "RENAME USER '{{ $username }}'@'{{ $host }}' TO '{{ $username }}'@'{{ $newHost }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

if ! sudo mariadb -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"