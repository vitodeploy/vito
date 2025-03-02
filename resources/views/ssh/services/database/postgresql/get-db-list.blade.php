if ! sudo -u postgres psql -c "SELECT
    datname AS database_name,
    pg_encoding_to_char(encoding) AS charset,
    datcollate AS collation
    FROM pg_database;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
