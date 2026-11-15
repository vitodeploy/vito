if ! sudo -u postgres psql -v ON_ERROR_STOP=1 -d {!! escapeshellarg($database) !!} <<'VITO_REASSIGN_USER'
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '{{ $username }}') THEN
        REASSIGN OWNED BY "{{ $username }}" TO "{{ $owner ?? 'postgres' }}";
        DROP OWNED BY "{{ $username }}";
    END IF;
END $$;
VITO_REASSIGN_USER
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
