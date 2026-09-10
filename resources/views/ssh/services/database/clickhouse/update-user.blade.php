@if ($newPassword)
if ! sudo clickhouse-client -q "ALTER USER \`{{ $username }}\` IDENTIFIED WITH sha256_password BY '{{ $newPassword }}';"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

echo "User {{ $username }} updated"
