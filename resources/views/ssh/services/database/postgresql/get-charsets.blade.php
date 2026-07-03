if ! sudo -u postgres psql -c "SELECT DISTINCT collname as collation,
    CASE
        WHEN collencoding = -1 THEN current_setting('server_encoding')
        ELSE pg_encoding_to_char(collencoding)
    END as charset,
    '' as id,
    '' as \"default\",
    'Yes' as compiled,
    '' as sortlen,
    '' as pad_attribute
    FROM pg_collation
    WHERE collencoding = -1
        OR pg_encoding_to_char(collencoding) <> ''
    ORDER BY charset, \"collation\";";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
