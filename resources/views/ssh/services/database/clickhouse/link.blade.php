@php
    $grants = match($permission ?? 'admin') {
        'read' => 'SELECT, SHOW',
        'write' => 'SELECT, INSERT, ALTER, CREATE, DROP, TRUNCATE, OPTIMIZE',
        default => 'ALL'
    };
@endphp

if ! sudo clickhouse-client -q {!! escapeshellarg('REVOKE ALL ON `' . str_replace('`', '``', $database) . '`.* FROM `' . str_replace('`', '``', $username) . '`;') !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo clickhouse-client -q {!! escapeshellarg('GRANT ' . $grants . ' ON `' . str_replace('`', '``', $database) . '`.* TO `' . str_replace('`', '``', $username) . '`;') !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Linking to {{ $database }} finished"
