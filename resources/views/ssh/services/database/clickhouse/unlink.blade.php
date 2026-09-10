if ! sudo clickhouse-client -q {!! escapeshellarg('REVOKE ALL ON *.* FROM `' . str_replace('`', '``', $username) . '`;') !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
