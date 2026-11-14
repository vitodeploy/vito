@if ($newPassword)
@php($sql = "ALTER USER '{$username}'@'{$host}' IDENTIFIED BY '".addslashes($newPassword)."'")
if ! sudo mariadb -e "$(echo {{ base64_encode($sql) }} | base64 -d)"; then
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