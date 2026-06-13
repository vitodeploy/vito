if ! sudo -u postgres psql -v ON_ERROR_STOP=1 <<'EOSQL'
SELECT format(
    'CREATE DATABASE %I WITH ENCODING %L TEMPLATE template0 %s',
    '{{ $name }}',
    '{{ $charset }}',
    coalesce((
        SELECT CASE s.provider
            WHEN 'i' THEN format('LOCALE_PROVIDER icu ICU_LOCALE %L LC_COLLATE %L LC_CTYPE %L', s.locale, 'C', 'C')
            WHEN 'b' THEN format('LOCALE_PROVIDER builtin BUILTIN_LOCALE %L', s.locale)
            WHEN 'd' THEN ''
            ELSE format('LC_COLLATE %L LC_CTYPE %L', s.collate, s.ctype)
        END
        FROM (
            SELECT
                to_jsonb(c)->>'collprovider' AS provider,
                coalesce(to_jsonb(c)->>'colllocale', to_jsonb(c)->>'colliculocale') AS locale,
                to_jsonb(c)->>'collcollate' AS collate,
                to_jsonb(c)->>'collctype'   AS ctype
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
