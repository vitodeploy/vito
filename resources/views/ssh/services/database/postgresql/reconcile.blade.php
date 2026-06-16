DB_NAME='{{ $database }}'
DB_VERSION='{{ $version }}'
DB_MAJOR=${DB_VERSION%%.*}

DB_OWNER=$(sudo -u postgres psql -d "$DB_NAME" -tAc "SELECT pg_catalog.pg_get_userbyid(d.datdba) FROM pg_catalog.pg_database d WHERE d.datname = '$DB_NAME';")

@foreach ($scrubUsers as $scrubUser)
if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname = '{{ $scrubUser }}'" | grep -q 1; then
    if ! sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA public FROM \"{{ $scrubUser }}\" CASCADE;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public FROM \"{{ $scrubUser }}\" CASCADE;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public FROM \"{{ $scrubUser }}\" CASCADE;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -c "REVOKE ALL PRIVILEGES ON DATABASE \"$DB_NAME\" FROM \"{{ $scrubUser }}\" CASCADE;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL ON SCHEMA public FROM \"{{ $scrubUser }}\" CASCADE;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
@foreach ($revokeCreators as $revokeCreator)
    if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname = '{{ $revokeCreator }}'" | grep -q 1; then
        if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"{{ $revokeCreator }}\" IN SCHEMA public REVOKE ALL ON TABLES FROM \"{{ $scrubUser }}\";"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"{{ $revokeCreator }}\" IN SCHEMA public REVOKE ALL ON SEQUENCES FROM \"{{ $scrubUser }}\";"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"{{ $revokeCreator }}\" IN SCHEMA public REVOKE ALL ON FUNCTIONS FROM \"{{ $scrubUser }}\";"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
    fi
@endforeach
    if [ -n "$DB_OWNER" ]; then
        if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"$DB_OWNER\" IN SCHEMA public REVOKE ALL ON TABLES FROM \"{{ $scrubUser }}\";"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"$DB_OWNER\" IN SCHEMA public REVOKE ALL ON SEQUENCES FROM \"{{ $scrubUser }}\";"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"$DB_OWNER\" IN SCHEMA public REVOKE ALL ON FUNCTIONS FROM \"{{ $scrubUser }}\";"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
    fi
fi
@endforeach

@foreach ($users as $user)
@php
    $username = $user['username'];
    $permission = $user['permission'];
    $tablePrivileges = match ($permission) {
        'read' => 'SELECT',
        'write' => 'SELECT, INSERT, UPDATE, DELETE, REFERENCES, TRIGGER',
        default => 'ALL PRIVILEGES',
    };
    $sequencePrivileges = match ($permission) {
        'read' => 'SELECT',
        'write' => 'USAGE, SELECT, UPDATE',
        default => 'ALL PRIVILEGES',
    };
    $functionPrivileges = match ($permission) {
        'read' => null,
        'write' => 'EXECUTE',
        default => 'ALL PRIVILEGES',
    };
    $databasePrivileges = $permission === 'admin' ? 'ALL PRIVILEGES' : 'CONNECT';
@endphp
if ! sudo -u postgres psql -c "GRANT {{ $databasePrivileges }} ON DATABASE \"$DB_NAME\" TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@if ($permission === 'read')
if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE ON SCHEMA public TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@elseif ($permission === 'write')
if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE ON SCHEMA public TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if [ "$DB_MAJOR" -ge 15 ]; then
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT CREATE ON SCHEMA public TO \"{{ $username }}\";"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi
@else
if [ "$DB_MAJOR" -ge 15 ]; then
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE, CREATE ON SCHEMA public TO \"{{ $username }}\";"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi
@endif
if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT {{ $tablePrivileges }} ON ALL TABLES IN SCHEMA public TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT {{ $sequencePrivileges }} ON ALL SEQUENCES IN SCHEMA public TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@if ($functionPrivileges)
if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT {{ $functionPrivileges }} ON ALL FUNCTIONS IN SCHEMA public TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif
@foreach ($grantCreators as $grantCreator)
if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"{{ $grantCreator }}\" IN SCHEMA public GRANT {{ $tablePrivileges }} ON TABLES TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"{{ $grantCreator }}\" IN SCHEMA public GRANT {{ $sequencePrivileges }} ON SEQUENCES TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@if ($functionPrivileges)
if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"{{ $grantCreator }}\" IN SCHEMA public GRANT {{ $functionPrivileges }} ON FUNCTIONS TO \"{{ $username }}\";"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif
@endforeach
if [ -n "$DB_OWNER" ]; then
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"$DB_OWNER\" IN SCHEMA public GRANT {{ $tablePrivileges }} ON TABLES TO \"{{ $username }}\";"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"$DB_OWNER\" IN SCHEMA public GRANT {{ $sequencePrivileges }} ON SEQUENCES TO \"{{ $username }}\";"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
@if ($functionPrivileges)
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE \"$DB_OWNER\" IN SCHEMA public GRANT {{ $functionPrivileges }} ON FUNCTIONS TO \"{{ $username }}\";"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
@endif
fi
@endforeach

echo "Privileges synced for $DB_NAME"
