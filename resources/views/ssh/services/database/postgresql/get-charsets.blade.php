if ! sudo -u postgres psql -c "SELECT collname as collation,
    pg_encoding_to_char(collencoding) as charset,
    '' as id,
    '' as \"default\",
    'Yes' as compiled,
    '' as sortlen,
    '' as pad_attribute
    FROM pg_collation
    WHERE not pg_encoding_to_char(collencoding) = ''
    ORDER BY charset;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
