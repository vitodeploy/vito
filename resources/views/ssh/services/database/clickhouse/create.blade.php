if ! sudo clickhouse-client -q {!! escapeshellarg('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $name) . '`;') !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
