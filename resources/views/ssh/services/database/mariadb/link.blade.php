@php
    $grants = match($permission ?? 'admin') {
        'read' => 'SELECT, SHOW VIEW',
        'write' => 'SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, LOCK TABLES, REFERENCES, SHOW VIEW, TRIGGER, CREATE VIEW, EXECUTE',
        default => 'ALL PRIVILEGES'
    };
@endphp

# Revoke all privileges first to ensure clean state
if ! sudo mariadb -e "REVOKE ALL PRIVILEGES ON {{ $database }}.* FROM '{{ $username }}'@'{{ $host }}'"; then
    # Ignore error if user has no privileges yet
    true
fi

# Grant the specific privileges
if ! sudo mariadb -e "GRANT {{ $grants }} ON {{ $database }}.* TO '{{ $username }}'@'{{ $host }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mariadb -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Linking to {{ $database }} finished"
