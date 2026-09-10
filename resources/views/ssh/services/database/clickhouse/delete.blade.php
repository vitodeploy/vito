if ! sudo clickhouse-client -q "DROP DATABASE IF EXISTS \`{{ $name }}\`;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
