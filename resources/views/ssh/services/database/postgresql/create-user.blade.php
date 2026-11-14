@if ($managed ?? false)
if ! sudo -u postgres psql -v ON_ERROR_STOP=1 -v password="$(echo {{ base64_encode($password) }} | base64 -d)" 2>/dev/null <<'VITO_MANAGED_USER'
SELECT
@if ($owner ?? false)
    'CREATE ROLE "{{ $username }}" WITH NOLOGIN'
@else
    format('CREATE ROLE "{{ $username }}" WITH LOGIN PASSWORD %L', :'password')
@endif
WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '{{ $username }}')
\gexec
VITO_MANAGED_USER
then
@else
if ! sudo -u postgres psql -v ON_ERROR_STOP=1 -v password="$(echo {{ base64_encode($password) }} | base64 -d)" <<'VITO_CREATE_USER'
CREATE ROLE "{{ $username }}" WITH LOGIN PASSWORD :'password';
VITO_CREATE_USER
then
@endif
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} created"
