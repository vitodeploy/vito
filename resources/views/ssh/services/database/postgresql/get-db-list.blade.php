if ! sudo -u postgres psql -c "SELECT
    d.datname AS database_name,
    pg_encoding_to_char(d.encoding) AS charset,
    CASE
        WHEN to_jsonb(d)->>'datlocprovider' = 'i'
            THEN coalesce(coalesce(to_jsonb(d)->>'datlocale', to_jsonb(d)->>'daticulocale') || '-x-icu', d.datcollate)
        WHEN to_jsonb(d)->>'datlocprovider' = 'b'
            THEN CASE coalesce(to_jsonb(d)->>'datlocale', to_jsonb(d)->>'daticulocale')
                WHEN 'C.UTF-8' THEN 'pg_c_utf8'
                WHEN 'PG_UNICODE_FAST' THEN 'pg_unicode_fast'
                ELSE coalesce(to_jsonb(d)->>'datlocale', to_jsonb(d)->>'daticulocale')
            END
        ELSE d.datcollate
    END AS collation
    FROM pg_database d;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
