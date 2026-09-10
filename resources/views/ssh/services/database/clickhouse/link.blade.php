@php
    $grants = match($permission ?? 'admin') {
        'read' => 'SELECT, SHOW',
        'write' => 'SELECT, INSERT, ALTER, CREATE, DROP, TRUNCATE, OPTIMIZE',
        default => 'ALL'
    };
@endphp

sudo clickhouse-client -q "REVOKE ALL ON \`{{ $database }}\`.* FROM \`{{ $username }}\`;" 2>/dev/null || true

if ! sudo clickhouse-client -q "GRANT {{ $grants }} ON \`{{ $database }}\`.* TO \`{{ $username }}\`;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Linking to {{ $database }} finished"
