@if ($newPassword)
if ! sudo -u postgres psql -c "ALTER ROLE \"{{ $username }}\" WITH PASSWORD '{{ $newPassword }}';"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif


echo "User {{ $username }} updated"