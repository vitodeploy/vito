@if ($managed ?? false)
if ! sudo -u postgres psql -v ON_ERROR_STOP=1 2>/dev/null <<'VITO_MANAGED_USER'
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '{{ $username }}') THEN
        CREATE ROLE "{{ $username }}" WITH @if ($owner ?? false)NOLOGIN @else LOGIN PASSWORD '{{ $password }}' @endif;
    END IF;
END $$;
VITO_MANAGED_USER
then
@else
if ! sudo -u postgres psql -c "CREATE ROLE \"{{ $username }}\" WITH LOGIN PASSWORD '{{ $password }}';"; then
@endif
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} created"
