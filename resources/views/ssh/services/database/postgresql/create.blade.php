if ! sudo -u postgres psql -v ON_ERROR_STOP=1 <<'EOSQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_collation WHERE collname = '{{ $collation }}') THEN
        RAISE EXCEPTION 'collation "%" does not exist', '{{ $collation }}';
    END IF;
END $$;
SELECT format(
    'CREATE DATABASE %I WITH ENCODING %L TEMPLATE template0 %s',
    '{{ $name }}',
    '{{ $charset }}',
    coalesce((
        SELECT CASE
            WHEN s.provider = 'i' AND s.icu_supported AND s.locale IS NOT NULL
                THEN format('LOCALE_PROVIDER icu ICU_LOCALE %L LC_COLLATE %L LC_CTYPE %L', s.locale, 'C', 'C')
            WHEN s.provider = 'b' AND s.builtin_supported AND s.locale IS NOT NULL
                THEN format('LOCALE_PROVIDER builtin BUILTIN_LOCALE %L', s.locale)
            WHEN s.provider = 'c' AND s.collate IS NOT NULL
                THEN format('LC_COLLATE %L LC_CTYPE %L', s.collate, s.ctype)
            ELSE ''
        END
        FROM (
            SELECT
                to_jsonb(c)->>'collprovider' AS provider,
                coalesce(to_jsonb(c)->>'colllocale', to_jsonb(c)->>'colliculocale') AS locale,
                to_jsonb(c)->>'collcollate' AS collate,
                to_jsonb(c)->>'collctype'   AS ctype,
                current_setting('server_version_num')::int >= 150000 AS icu_supported,
                current_setting('server_version_num')::int >= 170000 AS builtin_supported
            FROM pg_collation c
            WHERE c.collname = '{{ $collation }}'
            ORDER BY c.collencoding DESC
            LIMIT 1
        ) s
    ), '')
)
\gexec
EOSQL
then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Database {{ $name }} created"
