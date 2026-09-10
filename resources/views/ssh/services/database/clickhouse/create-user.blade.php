if ! sudo clickhouse-client -q {!! escapeshellarg("CREATE USER IF NOT EXISTS `" . str_replace('`', '``', $username) . "` IDENTIFIED WITH sha256_hash BY '" . hash('sha256', $password) . "' HOST ANY;") !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} created"
