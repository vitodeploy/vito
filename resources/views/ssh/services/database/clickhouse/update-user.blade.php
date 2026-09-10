@if ($newPassword)
if ! sudo clickhouse-client -q {!! escapeshellarg("ALTER USER `" . str_replace('`', '``', $username) . "` IDENTIFIED WITH sha256_hash BY '" . hash('sha256', $newPassword) . "';") !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

echo "User {{ $username }} updated"
