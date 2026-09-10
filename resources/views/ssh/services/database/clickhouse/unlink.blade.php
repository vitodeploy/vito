if ! sudo clickhouse-client -q "REVOKE ALL ON *.* FROM \`{{ $username }}\`;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
