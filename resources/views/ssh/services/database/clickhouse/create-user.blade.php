if ! sudo clickhouse-client -q "CREATE USER IF NOT EXISTS \`{{ $username }}\` IDENTIFIED WITH sha256_password BY '{{ $password }}' HOST ANY;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} created"
