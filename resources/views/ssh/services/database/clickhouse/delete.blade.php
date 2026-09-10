if ! sudo clickhouse-client -q {!! escapeshellarg('DROP DATABASE IF EXISTS `' . str_replace('`', '``', $name) . '`;') !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
