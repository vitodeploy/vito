if ! sudo clickhouse-client -q "DROP USER IF EXISTS \`{{ $username }}\`;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} deleted"
