@if ($newPassword)
@php($sql = "ALTER USER '{$username}'@'{$host}' IDENTIFIED BY '".addslashes($newPassword)."'")
if ! sudo mysql -e {!! escapeshellarg($sql) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

@if ($newHost && $newHost != $host)
if ! sudo mysql -e "RENAME USER '{{ $username }}'@'{{ $host }}' TO '{{ $username }}'@'{{ $newHost }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"