if ! sudo clickhouse-client -q {!! escapeshellarg('DROP USER IF EXISTS `' . str_replace('`', '``', $username) . '`;') !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} deleted"
