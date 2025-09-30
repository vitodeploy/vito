@if ($newPassword)
if ! sudo -u postgres psql -c "ALTER ROLE \"{{ $username }}\" WITH PASSWORD '{{ $newPassword }}';"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

@if ($newHost && $newHost != $host)
if ! sudo -u postgres psql -c "ALTER ROLE \"{{ $username }}\" RENAME TO \"{{ $username }}_temp\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo -u postgres psql -c "ALTER ROLE \"{{ $username }}_temp\" RENAME TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

echo "User {{ $username }} updated"