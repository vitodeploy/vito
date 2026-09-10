if ! sudo clickhouse-client -q "CREATE DATABASE IF NOT EXISTS \`{{ $name }}\`;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
